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
            <form action="departments.php<?php echo isset($department_id) ? "?edit={$department_id}" : ""; ?>" method="POST">
                <?php if (isset($department_id)): ?>
                    <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Department Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo isset($description) ? $description : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="color">Department Color</label>
                    <input type="color" id="color" name="color" value="<?php echo isset($color) ? $color : '#6c757d'; ?>" required>
                    <small class="form-text">Choose a color to represent this department</small>
                </div>

                <div class="form-actions">
                    <a href="departments.php" class="btn outline-secondary">Cancel</a>
                    <button type="submit" class="btn"><?php echo $button_text; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-content">
        <h2 class="page-title">Departments</h2>

        <div>
            <i>Note: Departments that have staff members assigned to them cannot be deleted.</i>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Color</th>
                    <th>Staff Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
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
                        ?>
                        <tr style="--dept-color: <?php echo $dept['color']; ?>">
                            <td><?php echo $dept['name']; ?></td>
                            <td><?php echo $dept['description'] ? substr($dept['description'], 0, 100) . (strlen($dept['description']) > 100 ? '...' : '') : ''; ?></td>
                            <td>
                                <?php
                                // Function to determine if text should be light or dark based on background color
                                $hex = ltrim($dept['color'], '#');
                                $r = hexdec(substr($hex, 0, 2));
                                $g = hexdec(substr($hex, 2, 2));
                                $b = hexdec(substr($hex, 4, 2));
                                // Calculate luminance - if luminance is greater than 150, use dark text
                                $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                $text_class = ($luminance > 150) ? 'dark-text' : 'light-text';
                                ?>
                                <div class="pill color-pill <?php echo $text_class; ?>" style="background-color: <?php echo $dept['color']; ?>">
                                    <?php echo $dept['color']; ?>
                                </div>
                            </td>
                            <td><?php echo $staff_count; ?></td>
                            <td class="action-buttons">
                                <a href="departments.php?edit=<?php echo $dept['id']; ?>" class="btn outline-secondary"><i class="lni lni-file-pencil"></i> Edit</a>
                                <a href="departments.php?delete=<?php echo $dept['id']; ?>" class="btn outline-danger <?php echo ($staff_count > 0) ? 'disabled' : ''; ?>"
                                    <?php if ($staff_count > 0): ?>onclick="alert('Cannot delete a department that has staff members assigned to it.'); return false;" <?php endif; ?>>
                                    <i class="lni lni-trash-3"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No departments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add confirmation for delete buttons
        const deleteButtons = document.querySelectorAll('.outline-danger:not(.disabled)')
        if (deleteButtons) {
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this department?')) {
                        e.preventDefault()
                    }
                })
            })
        }
    });
</script>

<?php require_once '../includes/admin_footer.php'; ?>