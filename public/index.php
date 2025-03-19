<?php
require_once 'includes/header.php';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'last_name';
$sort_order = isset($_GET['order']) ? sanitize_input($_GET['order']) : 'ASC';

// Get all staff members
$staff_members = get_all_staff_members($conn, $sort_by, $sort_order, $search, $department);

// Get all department names for filter dropdown
$departments = get_all_department_names($conn);
?>

<h1 class="page-title">Staff Directory</h1>

<div class="controls">
    <div class="search-box">
        <input type="text" id="search" placeholder="Search by name or job title..." value="<?php echo $search; ?>">
    </div>

    <div class="filter-box">
        <select id="department-filter">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept; ?>" <?php echo ($department == $dept) ? 'selected' : ''; ?>>
                    <?php echo $dept; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="sort-box">
        <select id="sort">
            <option value="name-asc" <?php echo ($sort_by == 'last_name' && $sort_order == 'ASC') ? 'selected' : ''; ?>>Name (A-Z)</option>
            <option value="name-desc" <?php echo ($sort_by == 'last_name' && $sort_order == 'DESC') ? 'selected' : ''; ?>>Name (Z-A)</option>
            <option value="department-asc" <?php echo ($sort_by == 'department' && $sort_order == 'ASC') ? 'selected' : ''; ?>>Department (A-Z)</option>
            <option value="department-desc" <?php echo ($sort_by == 'department' && $sort_order == 'DESC') ? 'selected' : ''; ?>>Department (Z-A)</option>
        </select>
    </div>
</div>

<div class="staff-grid" id="staff-grid">
    <?php if (count($staff_members) > 0): ?>
        <?php foreach ($staff_members as $staff): ?>
            <div class="staff-card">
                <img src="<?php echo get_staff_image_url($staff, '600x400'); ?>" alt="<?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?>" class="staff-image">
                <div class="staff-details">
                    <h3 class="staff-name"><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></h3>
                    <p class="staff-job"><?php echo $staff['job_title']; ?></p>
                    <p class="staff-department"><?php echo $staff['department']; ?></p>
                    <p class="staff-email"><a href="mailto:<?php echo $staff['email']; ?>"><?php echo $staff['email']; ?></a></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No staff members found.</p>
    <?php endif; ?>
</div>

<script src="assets/js/main.js"></script>

<?php require_once 'includes/footer.php'; ?>
