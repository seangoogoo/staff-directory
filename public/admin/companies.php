<?php
/**
 * Companies Management
 * Allows administrators to view, add, edit, and delete companies
 * Includes logo upload functionality and description editor
 */
require_once '../includes/admin_header.php';

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
            $success_message = "Company deleted successfully.";
        } else {
            $error_message = "Error deleting company: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Cannot delete company because it is assigned to {$staff_count} staff member(s).";
    }
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
            $error_message = "Error uploading logo. Please check file type and size.";
        }
    }

    // Validate required fields
    if (empty($name)) {
        $error_message = "Company name is required.";
    } else if (!isset($error_message)) {
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
                $success_message = "Company updated successfully.";
                // Reset form fields
                unset($name, $description, $company_id);
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    $error_message = "A company with this name already exists.";
                } else {
                    $error_message = "Error updating company: " . $stmt->error;
                }
            }
            $stmt->close();
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
                $success_message = "Company added successfully.";
                // Reset form fields
                unset($name, $description);
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    $error_message = "A company with this name already exists.";
                } else {
                    $error_message = "Error adding company: " . $stmt->error;
                }
            }
            $stmt->close();
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
        $error_message = "Company not found.";
    }
} else {
    $form_title = "Add New Company";
    $button_text = "Add Company";
}

// Get all companies
$companies = get_all_companies($conn);
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <h1 class="page-title"><?php echo $form_title; ?></h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <form action="companies.php<?php echo isset($company_id) ? "?edit={$company_id}" : ""; ?>" method="POST" enctype="multipart/form-data">
                <?php if (isset($company_id)): ?>
                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Company Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6"><?php echo isset($description) ? $description : ''; ?></textarea>
                    <small class="form-text">Describe the company, its mission, and core activities</small>
                </div>

                <div class="form-group">
                    <label for="logo">Company Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp" class="dropzone-input">
                    <div class="dropzone" id="logo-dropzone">
                        <div class="dropzone-icon">
                            <i class="lni lni-cloud-upload"></i>
                        </div>
                        <div class="dropzone-text">Drag & drop your logo here</div>
                        <div class="dropzone-subtext">or click to browse files (JPG, PNG, GIF, SVG or WEBP, max 5MB)</div>
                        <div class="dropzone-file-info" style="display: none;"></div>
                    </div>
                    <div class="image-preview-container logo-preview">
                        <?php
                        // Determine if we have an existing logo or need to show placeholder
                        $img_src = (isset($logo) && !empty($logo)) ? $logo : '/assets/images/add-picture.svg';
                        $is_placeholder = !(isset($logo) && !empty($logo));
                        ?>
                        <img id="image-preview" src="<?php echo $img_src; ?>" alt="Logo Preview" data-is-placeholder="<?php echo $is_placeholder ? 'true' : 'false'; ?>">
                        <div class="remove-image" id="remove-image" style="display: <?php echo $is_placeholder ? 'none' : 'flex'; ?>">
                            <i class="lni lni-xmark"></i>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="companies.php" class="btn outline-secondary">Cancel</a>
                    <button type="submit" class="btn"><?php echo $button_text; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-content">
        <h2 class="page-title">Companies</h2>

        <div>
            <i>Note: Companies that have staff members assigned to them cannot be deleted.</i>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Staff Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($companies) > 0): ?>
                    <?php foreach ($companies as $company): ?>
                        <?php
                        // Get staff count for this company
                        $staff_count = get_company_staff_count($conn, $company['id']);
                        ?>
                        <tr>
                            <td class="logo-cell">
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?php echo $company['logo']; ?>" alt="<?php echo $company['name']; ?> Logo" class="company-logo">
                                <?php else: ?>
                                    <div class="no-logo">No Logo</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $company['name']; ?></td>
                            <td><?php echo $company['description'] ? substr(strip_tags($company['description']), 0, 100) . (strlen(strip_tags($company['description'])) > 100 ? '...' : '') : ''; ?></td>
                            <td><?php echo $staff_count; ?></td>
                            <td class="action-buttons">
                                <a href="companies.php?edit=<?php echo $company['id']; ?>" class="btn outline-secondary"><i class="lni lni-file-pencil"></i> Edit</a>

                                <?php if ($staff_count == 0): ?>
                                    <a href="companies.php?delete=<?php echo $company['id']; ?>" class="btn outline-danger js-confirm"
                                       data-confirm="Are you sure you want to delete this company?">
                                       <i class="lni lni-trash-3"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <button class="btn disabled" title="Cannot delete: Has staff members">
                                        <i class="lni lni-trash-3"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No companies found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        }

        // Create the delete input if it doesn't exist
        if (!deleteInput) {
            deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_logo';
            deleteInput.value = '0';
            document.querySelector('form').appendChild(deleteInput);
        }

        // Handle file selection
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileInfo.textContent = file.name;
                fileInfo.style.display = 'block';

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.parentElement.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);

                // Reset delete flag
                deleteInput.value = '0';
            } else {
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
            dropzone.classList.add('dragover');
        }

        function unhighlight() {
            dropzone.classList.remove('dragover');
        }

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;

            // Trigger change event
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }

        // Handle click on dropzone
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        // Handle removal of image
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                if (imagePreview) {
                    // Set source to placeholder
                    imagePreview.src = '/assets/images/add-picture.svg';
                    imagePreview.dataset.isPlaceholder = 'true';
                }
                // Hide remove button
                removeButton.style.display = 'none';

                // Reset file input
                fileInput.value = '';
                fileInfo.style.display = 'none';

                // Set delete flag for form submission
                deleteInput.value = '1';
            });
        }

        // Add confirmation for delete buttons
        document.querySelectorAll('.js-confirm').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = e.currentTarget.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>
