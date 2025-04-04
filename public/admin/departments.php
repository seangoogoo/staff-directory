<?php

/**
 * Departments Management
 * Allows administrators to view, add, edit, and delete departments
 */
require_once '../includes/admin_header.php';

// Process delete request if submitted
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = sanitize_input($_GET['delete']);

    // Check if department is in use by any staff members
    $sql = "SELECT COUNT(*) as count FROM staff_members WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $staff_count = $row['count'];
    $stmt->close();

    // Only delete if the department is not in use
    if ($staff_count == 0) {
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $success_message = "Department deleted successfully.";
        } else {
            $error_message = "Error deleting department: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Cannot delete department because it is assigned to {$staff_count} staff member(s).";
    }
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
        $error_message = "Department name is required.";
    } else {
        // Check if this is an edit or add operation
        if ($department_id) {
            // Update existing department
            $sql = "UPDATE departments SET name = ?, description = ?, color = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $description, $color, $department_id);

            if ($stmt->execute()) {
                $success_message = "Department updated successfully.";
                // Reset form fields
                unset($name, $description, $color, $department_id);
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    $error_message = "A department with this name already exists.";
                } else {
                    $error_message = "Error updating department: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            // Add new department
            $sql = "INSERT INTO departments (name, description, color) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $description, $color);

            if ($stmt->execute()) {
                $success_message = "Department added successfully.";
                // Reset form fields
                unset($name, $description, $color);
            } else {
                // Check for duplicate entry error
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    $error_message = "A department with this name already exists.";
                } else {
                    $error_message = "Error adding department: " . $stmt->error;
                }
            }
            $stmt->close();
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
        $form_title = "Edit Department";
        $button_text = "Update Department";
    } else {
        $error_message = "Department not found.";
    }
} else {
    $form_title = "Add New Department";
    $button_text = "Add Department";
}

// Get all departments
$departments = get_all_departments($conn);
?>

<!-- Main content wrapper with flex layout -->
<div class="flex flex-col lg:flex-row gap-6">

    <!-- Left Column: Form -->
    <div class="lg:w-1/3">
        <h1 class="text-2xl font-semibold text-gray-900 mb-4"><?php echo $form_title; ?></h1>

        <?php if (isset($error_message)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
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
                    <label for="name" class="block text-sm font-medium text-gray-700">Department Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">Department Color</label>
                    <div class="mt-1 flex items-center">
                        <input type="color" id="color" name="color" value="<?php echo isset($color) ? htmlspecialchars($color) : '#6c757d'; ?>" required
                               class="h-8 w-12 rounded border-gray-300 cursor-pointer shadow-sm">
                        <span class="ml-2 text-gray-500"><?php echo isset($color) ? htmlspecialchars($color) : '#6c757d'; ?></span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Choose a color to represent this department</p>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <a href="departments.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo $button_text; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Table -->
    <div class="lg:w-2/3">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Departments</h2>

        <div class="text-sm text-gray-600 mb-4 italic">
            Note: Departments that have staff members assigned to them cannot be deleted.
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Count</th>
                        <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                            $text_color = ($luminance > 150) ? 'text-gray-900' : 'text-white';
                            ?>
                            <tr>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 text-center"><?php echo htmlspecialchars($dept['name']); ?></td>
                                <td class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($dept['description']); ?>">
                                    <?php echo $dept['description'] ? htmlspecialchars(substr($dept['description'], 0, 60)) . (strlen($dept['description']) > 60 ? '...' : '') : ''; ?>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $text_color; ?>" style="background-color: <?php echo htmlspecialchars($dept['color']); ?>">
                                        <?php echo htmlspecialchars($dept['color']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo $staff_count; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-right text-sm font-medium space-x-1">
                                    <a href="departments.php?edit=<?php echo $dept['id']; ?>" class="inline-flex justify-center items-center h-8 w-8 rounded-lg border border-indigo-200 text-indigo-400 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200" title="Edit">
                                        <i class="ri-pencil-line"></i>
                                    </a>

                                    <?php if ($staff_count == 0): ?>
                                        <a href="departments.php?delete=<?php echo $dept['id']; ?>" class="js-confirm inline-flex justify-center items-center h-8 w-8 rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300 transition-colors duration-200"
                                           data-confirm="Are you sure you want to delete this department?" title="Delete">
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
                            <td colspan="5" class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-500">No departments found.</td>
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
                if (!confirm(message || "Are you sure?")) {
                    e.preventDefault();
                }
            });
        });
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>