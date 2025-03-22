<?php
/**
 * Admin Dashboard
 * Requires authentication to access
 */

// Send cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Start a clean session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth first to check login status
require_once 'auth/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in, if not redirect to homepage with login modal
if (!is_logged_in()) {
    // Trigger the login popup on the homepage
    header("Location: /?login=required&redirect=admin");
    exit;
}

require_once '../includes/admin_header.php';

// Delete staff member if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = sanitize_input($_GET['delete']);
    if (delete_staff_member($conn, $id)) {
        $success_message = "Staff member deleted successfully.";
    } else {
        $error_message = "Error deleting staff member.";
    }
}

// Get all staff members
$staff_members = get_all_staff_members($conn);
?>

<h1 class="page-title">Staff Members Management</h1>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="admin-actions">
    <a href="add.php" class="btn"><i class="lni lni-plus"></i> Add New Staff Member</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Department</th>
            <th>Job Title</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($staff_members) > 0): ?>
            <?php foreach ($staff_members as $staff): ?>
                <tr>
                    <td>
                        <img src="<?php echo get_staff_image_url($staff, '50x50', null, $staff['department_color']); ?>" alt="<?php echo $staff['first_name']; ?>">
                    </td>
                    <td><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></td>
                    <td>
                        <?php
                        // Get proper text color contrast class
                        $text_class = get_text_contrast_class($staff['department_color']);
                        ?>
                        <div class="pill <?php echo $text_class; ?>" style="background-color: <?php echo $staff['department_color']; ?>">
                            <?php echo $staff['department']; ?>
                        </div>
                    </td>
                    <td><?php echo $staff['job_title']; ?></td>
                    <td><?php echo $staff['email']; ?></td>
                    <td class="action-buttons">
                        <a href="edit.php?id=<?php echo $staff['id']; ?>" class="btn outline-secondary"><i class="lni lni-file-pencil"></i> Edit</a>
                        <a href="index.php?delete=<?php echo $staff['id']; ?>" class="btn outline-danger"><i class="lni lni-trash-3"></i> Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No staff members found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="../assets/js/main.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>
