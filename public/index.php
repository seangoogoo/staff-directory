<?php
require_once 'includes/header.php';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$company = isset($_GET['company']) ? sanitize_input($_GET['company']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'last_name';
$sort_order = isset($_GET['order']) ? sanitize_input($_GET['order']) : 'ASC';

// Get all staff members
$staff_members = get_all_staff_members($conn, $sort_by, $sort_order, $search, $department, $company);

// Get all department names for filter dropdown
$departments = get_all_department_names($conn);

// Get all company names for filter dropdown
$companies = get_all_company_names($conn);
?>

<h1 class="page-title">Staff Directory</h1>

<div class="controls">
    <div class="search-box">
        <input type="text" id="search" placeholder="Search by name or job title..." value="<?php echo $search; ?>">
    </div>

    <div class="filter-box">
        <select id="company-filter">
            <option value="">All Companies</option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?php echo $comp; ?>" <?php echo ($company == $comp) ? 'selected' : ''; ?>>
                    <?php echo $comp; ?>
                </option>
            <?php endforeach; ?>
        </select>
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
            <option value="company-asc" <?php echo ($sort_by == 'company' && $sort_order == 'ASC') ? 'selected' : ''; ?>>Company (A-Z)</option>
            <option value="company-desc" <?php echo ($sort_by == 'company' && $sort_order == 'DESC') ? 'selected' : ''; ?>>Company (Z-A)</option>
        </select>
    </div>
</div>

<div class="staff-grid" id="staff-grid">
    <?php if (count($staff_members) > 0): ?>
        <?php foreach ($staff_members as $staff): ?>
            <div class="staff-card" style="--dept-color: <?php echo $staff['department_color']; ?>">
            <img src="<?php echo get_staff_image_url($staff, '600x400', null, $staff['department_color']); ?>" alt="<?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?>" class="staff-image">
            <div class="staff-details">
                    <!-- Company information -->
                    <?php if (!empty($staff['company'])): ?>
                    <div class="staff-company">
                        <?php if (!empty($staff['company_logo'])): ?>
                            <img src="<?php echo $staff['company_logo']; ?>" alt="<?php echo $staff['company']; ?> logo" class="company-logo">
                        <?php endif; ?>
                        <span class="company-name"><?php echo $staff['company']; ?></span>
                    </div>
                    <?php endif; ?>
                    <h3 class="staff-name"><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></h3>
                    <p class="staff-job dark-text"><?php echo $staff['job_title']; ?></p>
                    <p class="staff-department">
                        <?php
                        // Get proper text color contrast class
                        $text_class = get_text_contrast_class($staff['department_color']);
                        ?>
                        <span class="pill <?php echo $text_class; ?>" style="background-color: <?php echo $staff['department_color']; ?>">
                            <?php echo $staff['department']; ?>
                        </span>
                    </p>
                    <p class="staff-email"><a href="mailto:<?php echo $staff['email']; ?>" class="dark-text"><?php echo $staff['email']; ?></a></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No staff members found.</p>
    <?php endif; ?>
</div>

<script>
// // Initialize filters and sorting
// document.addEventListener('DOMContentLoaded', function() {
//     const searchInput = document.getElementById('search')
//     const departmentFilter = document.getElementById('department-filter')
//     const companyFilter = document.getElementById('company-filter')
//     const sortSelect = document.getElementById('sort')

//     // Handle search input changes
//     searchInput.addEventListener('input', function() {
//         applyFilters()
//     })

//     // Handle department filter changes
//     if (departmentFilter) {
//         departmentFilter.addEventListener('change', function() {
//             applyFilters()
//         })
//     }

//     // Handle company filter changes
//     if (companyFilter) {
//         companyFilter.addEventListener('change', function() {
//             applyFilters()
//         })
//     }

//     // Handle sort changes
//     if (sortSelect) {
//         sortSelect.addEventListener('change', function() {
//             applyFilters()
//         })
//     }

//     // Function to apply all filters and sorting
//     function applyFilters() {
//         const searchValue = searchInput.value.trim()
//         const departmentValue = departmentFilter ? departmentFilter.value : ''
//         const companyValue = companyFilter ? companyFilter.value : ''
//         const sortValue = sortSelect ? sortSelect.value : 'name-asc'

//         // Build URL with filter parameters
//         let url = window.location.pathname + '?'

//         // Add search parameter if not empty
//         if (searchValue) {
//             url += 'search=' + encodeURIComponent(searchValue) + '&'
//         }

//         // Add department filter if not empty
//         if (departmentValue) {
//             url += 'department=' + encodeURIComponent(departmentValue) + '&'
//         }

//         // Add company filter if not empty
//         if (companyValue) {
//             url += 'company=' + encodeURIComponent(companyValue) + '&'
//         }

//         // Add sort parameters
//         if (sortValue) {
//             const [sortBy, sortOrder] = sortValue.split('-');
//             url += 'sort=' + encodeURIComponent(sortBy) + '&order=' + encodeURIComponent(sortOrder.toUpperCase())
//         }

//         // Navigate to the URL with filters applied
//         window.location.href = url
//     }
// })
</script>

<script src="assets/js/main.js"></script>

<?php require_once 'includes/footer.php'; ?>
