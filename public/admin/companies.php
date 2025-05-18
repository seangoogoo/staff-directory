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
    $id = sanitize_input($_GET['delete'], false);

    // Check if company is in use by any staff members
    $staff_count = get_company_staff_count($conn, $id);

    // Only delete if the company is not in use
    if ($staff_count == 0) {
        // Get company info to delete logo if exists
        $company = get_company_by_id($conn, $id);

        $sql = "DELETE FROM " . TABLE_COMPANIES . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            // Delete company logo if it exists
            if (!empty($company['logo']) && file_exists(PUBLIC_PATH . $company['logo'])) {
                unlink(PUBLIC_PATH . $company['logo']);
            }
            set_session_message('success_message', __("company_deleted"));
        } else {
            set_session_message('error_message', __("error_deleting_company") . " " . $stmt->error);
        }
        $stmt->close();
    } else {
        set_session_message('error_message', str_replace('{count}', $staff_count, __("company_in_use_count")));
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: companies.php");
    exit;
}

// Process form submission for Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    // Don't encode HTML entities for database storage
    $name = sanitize_input($_POST['name'], false);
    $description = isset($_POST['description']) ? $_POST['description'] : ''; // Don't sanitize as it may contain HTML from editor
    $company_id = isset($_POST['company_id']) ? sanitize_input($_POST['company_id'], false) : null;

    // Process logo upload if provided
    $logo_path = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logo_path = upload_company_logo($_FILES['logo']);
        if ($logo_path === false) {
            set_session_message('error_message', __("error_uploading_logo"));
            header("Location: companies.php" . ($company_id ? "?edit={$company_id}" : ""));
            exit;
        }
    }

    // Validate required fields
    if (empty($name)) {
        set_session_message('error_message', __("company_name_required"));
        header("Location: companies.php" . ($company_id ? "?edit={$company_id}" : ""));
        exit;
    } else {
        // Check if this is an edit or add operation
        if ($company_id) {
            // Get current company to check for existing logo
            $current_company = get_company_by_id($conn, $company_id);

            // First check if new logo is uploaded - this takes precedence over delete flag
            if (!empty($logo_path)) {
                // New logo uploaded, update it and delete old one if exists
                $sql = "UPDATE " . TABLE_COMPANIES . " SET name = ?, description = ?, logo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $description, $logo_path, $company_id);

                // Delete old logo if it exists
                if (!empty($current_company['logo']) && file_exists(PUBLIC_PATH . $current_company['logo'])) {
                    unlink(PUBLIC_PATH . $current_company['logo']);
                }
            }
            // Then check if logo should be deleted (only if no new upload)
            else if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
                // Delete the logo file if it exists
                if (!empty($current_company['logo']) && file_exists(__DIR__ . '/..' . $current_company['logo'])) {
                    unlink(__DIR__ . '/..' . $current_company['logo']);
                }
                // Set logo to empty in the database
                $sql = "UPDATE " . TABLE_COMPANIES . " SET name = ?, description = ?, logo = '' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $description, $company_id);
            } else {
                // No new logo, keep existing one
                $sql = "UPDATE " . TABLE_COMPANIES . " SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $description, $company_id);
            }

            if ($stmt->execute()) {
                set_session_message('success_message', __("company_updated"));
                $stmt->close();
                header("Location: companies.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', __("company_duplicate"));
                } else {
                    set_session_message('error_message', __("error_updating_company") . " " . $stmt->error);
                }
                $stmt->close();
                header("Location: companies.php?edit={$company_id}");
                exit;
            }
        } else {
            // Add new company
            if (!empty($logo_path)) {
                $sql = "INSERT INTO " . TABLE_COMPANIES . " (name, description, logo) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $name, $description, $logo_path);
            } else {
                $sql = "INSERT INTO " . TABLE_COMPANIES . " (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $name, $description);
            }

            if ($stmt->execute()) {
                set_session_message('success_message', __("company_added"));
                $stmt->close();
                header("Location: companies.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', __("company_duplicate"));
                } else {
                    set_session_message('error_message', __("error_adding_company") . " " . $stmt->error);
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
    $edit_id = sanitize_input($_GET['edit'], false);
    $company = get_company_by_id($conn, $edit_id);

    if ($company) {
        $company_id = $company['id'];
        $name = $company['name'];
        $description = $company['description'];
        $logo = $company['logo'];
        $form_title = __("edit_company");
        $button_text = __("update_company");
    } else {
        set_session_message('error_message', __("company_not_found"));
        header("Location: companies.php");
        exit;
    }
} else {
    $form_title = __("add_new_company");
    $button_text = __("add_company");
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
                <input type="hidden" id="delete_logo" name="delete_logo" value="0">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700"><?php echo __("company_name"); ?></label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700"><?php echo __("description"); ?></label>
                    <textarea id="description" name="description" rows="6"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    <p class="mt-2 text-sm text-gray-500"><?php echo __("company_description_help"); ?></p>
                </div>

                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700"><?php echo __("company_logo"); ?></label>
                    <!-- Hidden file input -->
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp" class="hidden dropzone-input">
                    <!-- Dropzone -->
                    <div id="logo-dropzone" class="dropzone mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-500 hover:bg-gray-50 transition-colors duration-150">
                        <div class="space-y-1 text-center w-full">
                            <!-- Icon -->
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <span class="relative rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <?php echo __("drag_drop_logo"); ?>
                                </span>
                            </div>
                             <div class="dropzone-text hidden"><?php echo __("drag_drop_logo"); ?></div> <!-- Keep original hidden text for JS? -->
                            <p class="text-xs text-gray-500"><?php echo __("logo_file_types"); ?></p>
                            <div class="dropzone-file-info text-xs text-gray-500 font-medium mt-1" style="display: none;"></div>
                        </div>
                    </div>
                    <!-- Image Preview -->
                    <div class="mt-4 flex justify-center w-full">
                        <div class="relative inline-block logo-preview">
                             <?php
                            // Determine if we have an existing logo or need to show placeholder
                            $img_src = (isset($logo) && !empty($logo)) ? url($logo) : asset('images/add-picture.svg');
                            $is_placeholder = !(isset($logo) && !empty($logo));
                            $logger->debug("Image source: $img_src");
                            ?>
                            <img id="image-preview" src="<?php echo htmlspecialchars($img_src); ?>" alt="Logo Preview" data-is-placeholder="<?php echo $is_placeholder ? 'true' : 'false'; ?>"
                                 data-default-image="<?php echo asset('images/add-picture.svg'); ?>"
                                 class="h-48 w-48 object-cover rounded-md border border-gray-200">
                            <button type="button" id="remove-image"
                                    data-update-field="delete_logo"
                                    data-update-value="1"
                                    style="display: <?php echo $is_placeholder ? 'none' : 'flex'; ?>"
                                    class="absolute -top-2 -right-2 bg-gray-600 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <span class="sr-only"><?php echo __("remove_image"); ?></span>
                                <i class="ri-close-line text-sm"></i> <!-- Using Remix Icon -->
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <a href="companies.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"><?php echo __("cancel"); ?></a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo $button_text; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Table -->
    <div class="lg:w-2/3">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4"><?php echo __("companies"); ?></h2>

        <div class="text-sm text-gray-600 mb-4 italic">
            <?php echo __("company_note"); ?>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("logo"); ?></th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("name"); ?></th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("description"); ?></th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("staff_count"); ?></th>
                        <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("actions"); ?></th>
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
                                        <img src="<?php echo url(htmlspecialchars($company['logo'])); ?>" alt="<?php echo htmlspecialchars($company['name']); ?> Logo" class="h-10 w-10 object-cover inline-block rounded-lg">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-500 mx-auto"><?php echo __("no_logo"); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 text-center"><?php echo htmlspecialchars($company['name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars(strip_tags($company['description'])); ?>">
                                    <?php echo $company['description'] ? htmlspecialchars(substr(strip_tags($company['description']), 0, 60)) . (strlen(strip_tags($company['description'])) > 60 ? '...' : '') : ''; ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo $staff_count; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-right text-sm font-medium space-x-1">

                                    <a href="companies.php?edit=<?php echo $company['id']; ?>" class="inline-flex justify-center items-center h-8 w-8 rounded-lg border border-indigo-200 text-indigo-400 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200" title="<?php echo __("edit"); ?>">
                                        <i class="ri-pencil-line"></i>
                                    </a>

                                    <?php if ($staff_count == 0): ?>
                                        <a href="companies.php?delete=<?php echo $company['id']; ?>" class="js-confirm inline-flex justify-center items-center h-8 w-8 rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300 transition-colors duration-200"
                                           data-confirm="<?php echo __("confirm_delete_company"); ?>" title="<?php echo __("delete"); ?>">
                                            <i class="ri-delete-bin-line text-base"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed" title="<?php echo __("cannot_delete_has_staff"); ?>">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500"><?php echo __("no_companies_found"); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define default logo path using APP_BASE_URI
        const defaultLogo = window.APP_BASE_URI + '/assets/images/add-picture.svg'
        // Set up the file input and dropzone
        const fileInput = document.getElementById('logo')
        const dropzone = document.getElementById('logo-dropzone')
        const fileInfo = dropzone.querySelector('.dropzone-file-info')
        const imagePreview = document.getElementById('image-preview')
        const removeButton = document.getElementById('remove-image')
        let deleteInput = document.querySelector('input[name="delete_logo"]')

        // Set the correct placeholder status on page load
        const isPlaceholder = imagePreview.dataset.isPlaceholder === 'true'
        if (isPlaceholder) {
            removeButton.style.display = 'none'
            // Hide preview container if it's a placeholder initially? No, keep it visible for placeholder.
        } else {
            removeButton.style.display = 'flex' // Ensure button shows for actual images
        }


        // Create the delete input if it doesn't exist (should always exist if editing?)
        if (!deleteInput && document.querySelector('input[name="company_id"]')) { // Only create if potentially editing
            deleteInput = document.createElement('input')
            deleteInput.type = 'hidden'
            deleteInput.name = 'delete_logo'
            deleteInput.id = 'delete_logo' // Add ID for main.js data-update-field to work
            deleteInput.value = '0' // Default to not deleting
            document.querySelector('form').appendChild(deleteInput)
        } else if (deleteInput) {
            // If it exists but doesn't have an ID, add one
            if (!deleteInput.id) {
                deleteInput.id = 'delete_logo'
            }
            deleteInput.value = '0' // Ensure it's reset on load if it does exist
        }


        // Handle file selection via input click
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0]
                fileInfo.textContent = file.name
                fileInfo.style.display = 'block' // Show file name in dropzone area

                // Show preview
                const reader = new FileReader()
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.dataset.isPlaceholder = 'false' // Now it's not a placeholder
                        imagePreview.parentElement.style.display = 'inline-block' // Ensure container is visible
                        if(removeButton) removeButton.style.display = 'flex' // Show remove button
                    }
                }
                reader.readAsDataURL(file)

                // Reset delete flag if a new file is chosen
                if(deleteInput) deleteInput.value = '0'
            } else {
                // No file selected (e.g., user cancelled)
                // If it was showing a real image before, don't change it unless remove is clicked
                // If it was showing a placeholder, keep showing placeholder
                fileInfo.style.display = 'none'
            }
        })

        // Let main.js handle the dropzone functionality
        // We only need to keep the file input change handler


        // Let main.js handle the remove button functionality
        // We only need to ensure the delete_logo input exists with an ID

        // Add confirmation for delete buttons
        document.querySelectorAll('.js-confirm').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = e.currentTarget.getAttribute('data-confirm');
                if (!confirm(message || "<?php echo __("confirm"); ?>")) { // Provide default confirm message
                    e.preventDefault() // Prevent the link from being followed if confirmation is cancelled
                }
            })
        })
    })
</script>

<?php require_once '../includes/admin_footer.php'; ?>
