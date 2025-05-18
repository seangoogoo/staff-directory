<?php

/**
 * Departments Management
 * Allows administrators to view, add, edit, and delete departments
 */

// Define constant to indicate this is an admin page (required by admin_head.php)
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for initialization and security checks
require_once '../includes/admin_head.php';

// Get any flash messages stored in session
$error_message = get_session_message('error_message');
$success_message = get_session_message('success_message');

// Process delete request if submitted
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = sanitize_input($_GET['delete']);

    // Check if department is in use by any staff members
    $sql = "SELECT COUNT(*) as count FROM " . TABLE_STAFF_MEMBERS . " WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $staff_count = $row['count'];
    $stmt->close();

    // Only delete if the department is not in use
    if ($staff_count == 0) {
        $sql = "DELETE FROM " . TABLE_DEPARTMENTS . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            set_session_message('success_message', __("department_deleted"));
        } else {
            set_session_message('error_message', __("error_deleting_department") . " " . $stmt->error);
        }
        $stmt->close();
    } else {
        set_session_message('error_message', str_replace('{count}', $staff_count, __("department_in_use_count")));
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: departments.php");
    exit;
}

// Process form submission for Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $color = sanitize_input($_POST['color']);
    $department_id = isset($_POST['department_id']) ? sanitize_input($_POST['department_id']) : null;

    // Default color if not set
    if (empty($color)) {
        $color = '#6c757d';
    }

    // Validate required fields
    if (empty($name)) {
        set_session_message('error_message', __("department_name_required"));
        header("Location: departments.php" . ($department_id ? "?edit={$department_id}" : ""));
        exit;
    } else {
        // Check if this is an edit or add operation
        if ($department_id) {
            // Update existing department
            $sql = "UPDATE " . TABLE_DEPARTMENTS . " SET name = ?, description = ?, color = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $description, $color, $department_id);

            if ($stmt->execute()) {
                set_session_message('success_message', __("department_updated"));
                $stmt->close();
                header("Location: departments.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', __("department_duplicate"));
                } else {
                    set_session_message('error_message', __("error_updating_department") . " " . $stmt->error);
                }
                $stmt->close();
                header("Location: departments.php?edit={$department_id}");
                exit;
            }
        } else {
            // Add new department
            $sql = "INSERT INTO " . TABLE_DEPARTMENTS . " (name, description, color) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $description, $color);

            if ($stmt->execute()) {
                set_session_message('success_message', __("department_added"));
                $stmt->close();
                header("Location: departments.php");
                exit;
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    set_session_message('error_message', __("department_duplicate"));
                } else {
                    set_session_message('error_message', __("error_adding_department") . " " . $stmt->error);
                }
                $stmt->close();
                header("Location: departments.php");
                exit;
            }
        }
    }
}

// Load department for editing if ID is provided
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = sanitize_input($_GET['edit']);
    $department = get_department_by_id($conn, $edit_id);

    if ($department) {
        $department_id = $department['id'];
        $name = $department['name'];
        $description = $department['description'];
        $color = $department['color'];
        $form_title = __("edit_department");
        $button_text = __("update_department");
    } else {
        set_session_message('error_message', __("department_not_found"));
        header("Location: departments.php");
        exit;
    }
} else {
    $form_title = __("add_new_department");
    $button_text = __("add_department");
}

// Get all departments
$departments = get_all_departments($conn);

// Include the HTML header after all processing is done
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
            <form action="departments.php<?php echo isset($department_id) ? "?edit={$department_id}" : ""; ?>" method="POST" class="space-y-6">
                <?php if (isset($department_id)): ?>
                    <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700"><?php echo __("department_name"); ?></label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700"><?php echo __("description"); ?></label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700"><?php echo __("department_color"); ?></label>
                    <div class="mt-1 flex items-center">
                        <input type="color" id="color" name="color" value="<?php echo isset($color) ? htmlspecialchars($color) : '#6c757d'; ?>" required
                               class="h-8 w-12 rounded border-gray-300 cursor-pointer shadow-sm">
                        <span class="ml-2 text-gray-500"><?php echo isset($color) ? htmlspecialchars($color) : '#6c757d'; ?></span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500"><?php echo __("department_color_help"); ?></p>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <a href="departments.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"><?php echo __("cancel"); ?></a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo $button_text; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Table -->
    <div class="lg:w-2/3">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4"><?php echo __("departments"); ?></h2>

        <div class="text-sm text-gray-600 mb-4 italic">
            <?php echo __("department_note"); ?>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("name"); ?></th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("description"); ?></th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("color"); ?></th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("staff_count"); ?></th>
                        <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __("actions"); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($departments) > 0): ?>
                        <?php foreach ($departments as $dept): ?>
                            <?php
                            // Get staff count for this department
                            $sql = "SELECT COUNT(*) as count FROM staff_members WHERE department_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('i', $dept['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            $staff_count = $row['count'];
                            $stmt->close();

                            // Function to determine if text should be light or dark based on background color
                            $hex = ltrim($dept['color'], '#');
                            $r = hexdec(substr($hex, 0, 2));
                            $g = hexdec(substr($hex, 2, 2));
                            $b = hexdec(substr($hex, 4, 2));
                            // Calculate luminance - if luminance is greater than 150, use dark text
                            $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                            $text_class = get_text_contrast_class($dept['color']);
                            ?>
                            <tr style="--dept-color: <?php echo htmlspecialchars($dept['color']); ?>">
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 text-center"><?php echo htmlspecialchars($dept['name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($dept['description']); ?>">
                                    <?php echo $dept['description'] ? htmlspecialchars(substr($dept['description'], 0, 60)) . (strlen($dept['description']) > 60 ? '...' : '') : ''; ?>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $text_class; ?>" style="background-color: <?php echo htmlspecialchars($dept['color']); ?>">
                                        <?php echo htmlspecialchars($dept['color']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo $staff_count; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-right text-sm font-medium space-x-1">
                                    <a href="departments.php?edit=<?php echo $dept['id']; ?>" class="inline-flex justify-center items-center h-8 w-8 rounded-lg border border-indigo-200 text-indigo-400 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200" title="<?php echo __("edit"); ?>">
                                        <i class="ri-pencil-line"></i>
                                    </a>

                                    <?php if ($staff_count == 0): ?>
                                        <a href="departments.php?delete=<?php echo $dept['id']; ?>" class="js-confirm inline-flex justify-center items-center h-8 w-8 rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300 transition-colors duration-200"
                                           data-confirm="<?php echo __("confirm_delete_department"); ?>" title="<?php echo __("delete"); ?>">
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
                            <td colspan="5" class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500"><?php echo __("no_departments_found"); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sync the color input with a text display
        const colorInput = document.getElementById('color');
        if (colorInput) {
            // Find the adjacent span to display the color code
            const colorDisplay = colorInput.nextElementSibling;

            // Update color code when input changes
            colorInput.addEventListener('input', function() {
                if (colorDisplay) {
                    colorDisplay.textContent = this.value;
                }
            });
        }

        // Check if we're in edit mode and there was a success message
        const hasSuccessMessage = document.querySelector('.text-green-700');
        const isEditMode = window.location.href.includes('?edit=');
        if (hasSuccessMessage && isEditMode) {
            // Redirect to the main page after a short delay to allow the user to see the success message
            setTimeout(function() {
                window.location.href = 'departments.php';
            }, 1500);
        }

        // Add confirmation for delete buttons
        document.querySelectorAll('.js-confirm').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = e.currentTarget.getAttribute('data-confirm');
                if (!confirm(message || "<?php echo __("confirm"); ?>")) {
                    e.preventDefault();
                }
            });
        });
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>