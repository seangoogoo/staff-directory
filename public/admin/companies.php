<?php
/**
 * Companies Management
 * Allows administrators to view, add, edit, and delete companies
 * Includes logo upload functionality and description editor
 */

// Define constant to indicate this is an admin page (required by admin_head.php)
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for PHP initialization and security checks
require_once '../includes/admin_head.php';

// Get any flash messages stored in session
$error_message = get_session_message('error_message');
$success_message = get_session_message('success_message');

// Process delete request if submitted
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = sanitize_input($_GET['delete']);

    // Check if company is in use by any staff members
    $staff_count = get_company_staff_count($conn, $id);

    // Only delete if the company is not in use
    if ($staff_count == 0) {
        // Get company info to delete logo if exists
        $company = get_company_by_id($conn, $id);

        $sql = "DELETE FROM companies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            // Delete company logo if it exists
            if (!empty($company['logo']) && file_exists(__DIR__ . '/..' . $company['logo'])) {
                unlink(__DIR__ . '/..' . $company['logo']);
            }
            set_session_message('success_message', "Company deleted successfully.");
        } else {
            set_session_message('error_message', "Error deleting company: " . $stmt->error);
        }
        $stmt->close();
    } else {
        set_session_message('error_message', "Cannot delete company because it is assigned to {$staff_count} staff member(s).");
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: companies.php");
    exit;
}

// Process form submission for Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = sanitize_input($_POST['name']);
    $description = isset($_POST['description']) ? $_POST['description'] : ''; // Don't sanitize as it may contain HTML from editor
    $company_id = isset($_POST['company_id']) ? sanitize_input($_POST['company_id']) : null;

    // Process logo upload if provided
    $logo_path = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logo_path = upload_company_logo($_FILES['logo']);
        if ($logo_path === false) {
            set_session_message('error_message', "Error uploading logo. Please check file type and size.");
            header("Location: companies.php" . ($company_id ? "?edit={$company_id}" : ""));
            exit;
        }
    }

    // Validate required fields
    if (empty($name)) {
        set_session_message('error_message', "Company name is required.");
        header("Location: companies.php" . ($company_id ? "?edit={$company_id}" : ""));
        exit;
    } else {
        // Check if this is an edit or add operation
        if ($company_id) {
            // Get current company to check for existing logo
            $current_company = get_company_by_id($conn, $company_id);

            // Check if logo should be deleted
            if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
                // Delete the logo file if it exists
                if (!empty($current_company['logo']) && file_exists(__DIR__ . '/..' . $current_company['logo'])) {
                    unlink(__DIR__ . '/..' . $current_company['logo']);
                }
                // Set logo to empty in the database
                $sql = "UPDATE companies SET name = ?, description = ?, logo = '' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $description, $company_id);
            }
            // Check if new logo is uploaded
            else if (!empty($logo_path)) {
                // New logo uploaded, update it and delete old one if exists
                $sql = "UPDATE companies SET name = ?, description = ?, logo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $description, $logo_path, $company_id);

                // Delete old logo if it exists
                if (!empty($current_company['logo']) && file_exists(__DIR__ . '/..' . $current_company['logo'])) {
                    unlink(__DIR__ . '/..' . $current_company['logo']);
                }
            } else {
                // No new logo, keep existing one
                $sql = "UPDATE companies SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $description, $company_id);
            }

            if ($stmt->execute()) {
                set_session_message('success_message', "Company updated successfully.");
                $stmt->close();
                header("Location: companies.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', "A company with this name already exists.");
                } else {
                    set_session_message('error_message', "Error updating company: " . $stmt->error);
                }
                $stmt->close();
                header("Location: companies.php?edit={$company_id}");
                exit;
            }
        } else {
            // Add new company
            if (!empty($logo_path)) {
                $sql = "INSERT INTO companies (name, description, logo) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $name, $description, $logo_path);
            } else {
                $sql = "INSERT INTO companies (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $name, $description);
            }

            if ($stmt->execute()) {
                set_session_message('success_message', "Company added successfully.");
                $stmt->close();
                header("Location: companies.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', "A company with this name already exists.");
                } else {
                    set_session_message('error_message', "Error adding company: " . $stmt->error);
                }
                $stmt->close();
                header("Location: companies.php");
                exit;
            }
        }
    }
}

// Load company for editing if ID is provided
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = sanitize_input($_GET['edit']);
    $company = get_company_by_id($conn, $edit_id);

    if ($company) {
        $company_id = $company['id'];
        $name = $company['name'];
        $description = $company['description'];
        $logo = $company['logo'];
        $form_title = "Edit Company";
        $button_text = "Update Company";
    } else {
        set_session_message('error_message', "Company not found.");
        header("Location: companies.php");
        exit;
    }
} else {
    $form_title = "Add New Company";
    $button_text = "Add Company";
}

// Get all companies
$companies = get_all_companies($conn);

// Include header - this will output HTML
require_once '../includes/admin_header.php';
?>

<!-- Main content wrapper with flex layout -->
<div class="flex flex-col lg:flex-row gap-6">

    <!-- Left Column: Form -->
    <div class="lg:w-1/3">
        <h1 class="text-2xl font-semibold text-gray-900 mb-4"><?php echo $form_title; ?></h1>

        <?php if (!empty($error_message)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <form action="companies.php<?php echo isset($company_id) ? "?edit={$company_id}" : ""; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php if (isset($company_id)): ?>
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Company Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="6"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    <p class="mt-2 text-sm text-gray-500">Describe the company, its mission, and core activities</p>
                </div>

                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">Company Logo</label>
                    <!-- Hidden file input -->
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp" class="hidden">
                    <!-- Dropzone -->
                    <div id="logo-dropzone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-500 hover:bg-gray-50 transition-colors duration-150">
                        <div class="space-y-1 text-center w-full">
                            <!-- Icon -->
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <span class="relative rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    Drag & drop your logo here
                                </span>
                            </div>
                             <div class="dropzone-text hidden">Drag & drop your logo here</div> <!-- Keep original hidden text for JS? -->
                            <p class="text-xs text-gray-500">or click to browse files (JPG, PNG, GIF, SVG or WEBP, max 5MB)</p>
                            <div class="dropzone-file-info text-xs text-gray-500 font-medium mt-1" style="display: none;"></div>
                        </div>
                    </div>
                    <!-- Image Preview -->
                    <div class="mt-4 flex justify-center w-full">
                        <div class="relative inline-block logo-preview">
                             <?php
                            // Determine if we have an existing logo or need to show placeholder
                            $img_src = (isset($logo) && !empty($logo)) ? $logo : '/assets/images/add-picture.svg';
                            $is_placeholder = !(isset($logo) && !empty($logo));
                            ?>
                            <img id="image-preview" src="<?php echo htmlspecialchars($img_src); ?>" alt="Logo Preview" data-is-placeholder="<?php echo $is_placeholder ? 'true' : 'false'; ?>"
                                 class="h-48 w-48 object-cover rounded-md border border-gray-200">
                            <button type="button" id="remove-image" style="display: <?php echo $is_placeholder ? 'none' : 'flex'; ?>"
                                    class="absolute -top-2 -right-2 bg-gray-600 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <span class="sr-only">Remove image</span>
                                <i class="ri-close-line text-sm"></i> <!-- Using Remix Icon -->
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <a href="companies.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo $button_text; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Table -->
    <div class="lg:w-2/3">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Companies</h2>

        <div class="text-sm text-gray-600 mb-4 italic">
            Note: Companies that have staff members assigned to them cannot be deleted.
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Count</th>
                        <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($companies) > 0): ?>
                        <?php foreach ($companies as $company): ?>
                            <?php
                            // Get staff count for this company
                            $staff_count = get_company_staff_count($conn, $company['id']);
                            ?>
                            <tr>
                                <td class="px-3 py-2 text-center">
                                    <?php if (!empty($company['logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['name']); ?> Logo" class="h-10 w-10 object-cover inline-block rounded-lg">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-500 mx-auto">No Logo</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 text-center"><?php echo htmlspecialchars($company['name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars(strip_tags($company['description'])); ?>">
                                    <?php echo $company['description'] ? htmlspecialchars(substr(strip_tags($company['description']), 0, 60)) . (strlen(strip_tags($company['description'])) > 60 ? '...' : '') : ''; ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo $staff_count; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-right text-sm font-medium space-x-1">

                                    <a href="companies.php?edit=<?php echo $company['id']; ?>" class="inline-flex justify-center items-center h-8 w-8 rounded-lg border border-indigo-200 text-indigo-400 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200" title="Edit">
                                        <i class="ri-pencil-line"></i>
                                    </a>

                                    <?php if ($staff_count == 0): ?>
                                        <a href="companies.php?delete=<?php echo $company['id']; ?>" class="js-confirm inline-flex justify-center items-center h-8 w-8 rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300 transition-colors duration-200"
                                           data-confirm="Are you sure you want to delete this company?" title="Delete">
                                            <i class="ri-delete-bin-line text-base"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed" title="Cannot delete: Has staff members">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">No companies found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set up the file input and dropzone
        const fileInput = document.getElementById('logo');
        const dropzone = document.getElementById('logo-dropzone');
        const fileInfo = dropzone.querySelector('.dropzone-file-info');
        const imagePreview = document.getElementById('image-preview');
        const removeButton = document.getElementById('remove-image');
        let deleteInput = document.querySelector('input[name="delete_logo"]');

        // Set the correct placeholder status on page load
        const isPlaceholder = imagePreview.dataset.isPlaceholder === 'true';
        if (isPlaceholder) {
            removeButton.style.display = 'none';
            // Hide preview container if it's a placeholder initially? No, keep it visible for placeholder.
        } else {
            removeButton.style.display = 'flex'; // Ensure button shows for actual images
        }


        // Create the delete input if it doesn't exist (should always exist if editing?)
        if (!deleteInput && document.querySelector('input[name="company_id"]')) { // Only create if potentially editing
            deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_logo';
            deleteInput.value = '0'; // Default to not deleting
            document.querySelector('form').appendChild(deleteInput);
        } else if (deleteInput) {
             deleteInput.value = '0'; // Ensure it's reset on load if it does exist
        }


        // Handle file selection via input click
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileInfo.textContent = file.name;
                fileInfo.style.display = 'block'; // Show file name in dropzone area

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.dataset.isPlaceholder = 'false'; // Now it's not a placeholder
                        imagePreview.parentElement.style.display = 'inline-block'; // Ensure container is visible
                        if(removeButton) removeButton.style.display = 'flex'; // Show remove button
                    }
                };
                reader.readAsDataURL(file);

                // Reset delete flag if a new file is chosen
                if(deleteInput) deleteInput.value = '0';
            } else {
                // No file selected (e.g., user cancelled)
                // If it was showing a real image before, don't change it unless remove is clicked
                // If it was showing a placeholder, keep showing placeholder
                fileInfo.style.display = 'none';
            }
        });

        // Handle drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropzone.classList.add('border-indigo-500', 'bg-gray-50'); // Use Tailwind classes for highlighting
        }

        function unhighlight() {
            dropzone.classList.remove('border-indigo-500', 'bg-gray-50');
        }

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            // Basic validation (can enhance)
             if (files.length > 0) {
                 const file = files[0];
                 const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
                 if (allowedTypes.includes(file.type) && file.size <= 5 * 1024 * 1024) { // Max 5MB
                     fileInput.files = files; // Assign dropped files to the hidden input
                     // Trigger change event manually to update preview etc.
                     const event = new Event('change', { bubbles: true });
                     fileInput.dispatchEvent(event);
                 } else {
                     // Optional: Show an error message to the user
                     console.warn('Invalid file type or size dropped.');
                     alert('Invalid file type or size. Please use JPG, PNG, GIF, SVG or WEBP, max 5MB.');
                 }
             }
        }

        // Handle click on dropzone to trigger file input
        dropzone.addEventListener('click', function(e) {
            // Prevent triggering if clicking on the link text itself maybe? No, let it trigger.
             if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') { // Avoid triggering if clicking interactive elements within
                fileInput.click();
             }
        });


        // Handle removal of image
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                if (imagePreview) {
                    // Set source to placeholder SVG
                    imagePreview.src = '/assets/images/add-picture.svg'; // Make sure this path is correct
                    imagePreview.dataset.isPlaceholder = 'true'; // Mark as placeholder
                }
                // Hide remove button
                removeButton.style.display = 'none';

                // Reset file input & file info display
                fileInput.value = ''; // Clears the selected file
                fileInfo.textContent = '';
                fileInfo.style.display = 'none';

                // Set delete flag for form submission *if* a hidden input exists
                if (deleteInput) {
                    deleteInput.value = '1';
                }
            });
        }

        // Add confirmation for delete buttons
        document.querySelectorAll('.js-confirm').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = e.currentTarget.getAttribute('data-confirm');
                if (!confirm(message || "Are you sure?")) { // Provide default confirm message
                    e.preventDefault(); // Prevent the link from being followed if confirmation is cancelled
                }
            });
        });
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>
