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

// Get company statistics
$company_stats = get_all_company_statistics($conn);
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

<!-- Company Statistics Dashboard -->
<div class="stats-dashboard">
    <h2 class="section-title">Company Statistics</h2>
    <div class="stats-overview">
        <div class="stat-card total-staff">
            <div class="stat-icon">
                <i class="lni lni-user-multiple-4"></i>
            </div>
            <div class="stat-info">
                <h3>Total Staff</h3>
                <div class="stat-number"><?php echo $company_stats['total_staff']; ?></div>
            </div>
        </div>
    </div>

    <div class="company-stats-container">
        <?php if (!empty($company_stats['companies'])): ?>
            <?php foreach ($company_stats['companies'] as $company): ?>
                <div class="company-stat-card">
                    <div class="company-info">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="<?php echo $company['logo']; ?>" alt="<?php echo $company['name']; ?> logo" class="company-stat-logo">
                        <?php else: ?>
                            <div class="company-icon"><i class="lni lni-apartment"></i></div>
                        <?php endif; ?>
                        <h3><?php echo $company['name']; ?></h3>
                    </div>
                    <div class="company-metrics">
                        <div class="metric">
                            <span class="metric-label">Staff Count</span>
                            <span class="metric-value"><?php echo $company['staff_count']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Percentage</span>
                            <span class="metric-value"><?php echo $company['percentage']; ?>%</span>
                        </div>
                    </div>
                    <div class="company-progress">
                        <div class="progress-bar" style="width: <?php echo $company['percentage']; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No company data available.</p>
        <?php endif; ?>
    </div>
</div>

<table class="admin-table staff-members-table">
    <thead>
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Company</th>
            <th>Department</th>
            <th>Job Title</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($staff_members) > 0): ?>
            <?php foreach ($staff_members as $staff): ?>
                <tr style="--dept-color: <?php echo $staff['department_color']; ?>">
                    <td>
                        <img src="<?php echo get_staff_image_url($staff, '50x50', null, $staff['department_color']); ?>" alt="<?php echo $staff['first_name']; ?>">
                    </td>
                    <td><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></td>
                    <td>
                        <?php if (!empty($staff['company_logo'])): ?>
                            <div class="company-display">
                                <img src="<?php echo $staff['company_logo']; ?>" alt="<?php echo $staff['company']; ?> logo" class="company-logo">
                                <span class="company-name"><?php echo $staff['company']; ?></span>
                            </div>
                        <?php else: ?>
                            <span class="company-name-only"><?php echo $staff['company']; ?></span>
                        <?php endif; ?>
                    </td>
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
                <td colspan="7">No staff members found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="../assets/js/main.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>
