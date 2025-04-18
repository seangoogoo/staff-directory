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

// Get department names that have at least one staff member for filter dropdown
$departments = get_active_department_names($conn);

// Get company names that have at least one staff member for filter dropdown
$companies = get_active_company_names($conn);
?>

<?php if (isset($_GET['not_found']) && $_GET['not_found'] == '1'): ?>
    <!-- 404 Message for redirected not found pages -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="ri-error-warning-line text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <?php echo __('page_not_found_message'); ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Title Styling: mb-6, text-gray-700, font-thin -->
<h1 class="page-title mb-6 text-gray-700 font-thin text-4xl"><?php echo __('staff_directory'); ?></h1>

<!-- Controls Styling: flex, wrap, gap-4, mb-6, items-center -->
<div class="controls flex flex-wrap gap-4 mb-6 items-center">
    <!-- Search Box Styling: flex-1, min-w -->
    <div class="search-box flex-grow min-w-[250px]">
        <!-- Input styling: rounded-full, border, padding, shadow -->
        <input class="w-full rounded-full px-4 py-2 border border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="text" id="search" placeholder="<?php echo __('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <!-- Filter/Sort container -->
    <div class="flex flex-wrap gap-4">
        <!-- Filter Box Styling: min-w -->
        <div class="filter-box min-w-[180px]">
            <!-- Select styling: rounded-full, border, padding, shadow, bg -->
            <select id="company-filter" class="w-full rounded-full pl-4 pr-10 py-2 border border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white">
                <option value=""><?php echo __('all_companies'); ?></option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo ($company == $comp) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($comp); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-box min-w-[180px]">
             <!-- Select styling: rounded-full, border, padding, shadow, bg -->
            <select id="department-filter" class="w-full rounded-full pl-4 pr-10 py-2 border border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white">
                <option value=""><?php echo __('all_departments'); ?></option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department == $dept) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sort-box min-w-[180px]">
             <!-- Select styling: rounded-full, border, padding, shadow, bg -->
             <!-- Note: Values combine sort_by and sort_order for JS handling -->
            <select id="sort" class="w-full rounded-full pl-4 pr-10 py-2 border border-gray-200 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white">
                <option value="name-asc" <?php echo ($sort_by == 'last_name' && $sort_order == 'ASC') ? 'selected' : ''; ?>><?php echo __('name_asc'); ?></option>
                <option value="name-desc" <?php echo ($sort_by == 'last_name' && $sort_order == 'DESC') ? 'selected' : ''; ?>><?php echo __('name_desc'); ?></option>
                <option value="department-asc" <?php echo ($sort_by == 'department' && $sort_order == 'ASC') ? 'selected' : ''; ?>><?php echo __('department_asc'); ?></option>
                <option value="department-desc" <?php echo ($sort_by == 'department' && $sort_order == 'DESC') ? 'selected' : ''; ?>><?php echo __('department_desc'); ?></option>
                <option value="company-asc" <?php echo ($sort_by == 'company' && $sort_order == 'ASC') ? 'selected' : ''; ?>><?php echo __('company_asc'); ?></option>
                <option value="company-desc" <?php echo ($sort_by == 'company' && $sort_order == 'DESC') ? 'selected' : ''; ?>><?php echo __('company_desc'); ?></option>
            </select>
        </div>
    </div>
</div>

<!-- Staff Grid Styling: grid, cols, gap -->
<style>
    .staff-card {
        opacity: 0;
        transform: translateY(80px);
    }
    .card-visible {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 350ms ease-out, transform 350ms cubic-bezier(.17, .84, .44, 1);
    }
</style>
<div class="staff-grid grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-6 mb-8" id="staff-grid">
    <?php if (count($staff_members) > 0): ?>
        <?php foreach ($staff_members as $staff): ?>
            <?php
            // Get image URL or generate placeholder data
            $imageUrl = get_staff_image_url($staff, '600x400', null, $staff['department_color']);
            $placeholderInitials = strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1));
            $placeholderColor = $staff['department_color'] ?? '#cccccc'; // Use department color or gray
            $placeholderTextColor = get_text_contrast_class($placeholderColor) === 'light-text' ? '#ffffff' : '#333333';
            ?>
            <!-- Card Styling: grid rows, bg, rounded, shadow, overflow -->
            <div class="staff-card grid grid-rows-[auto_1fr] bg-white rounded-[20px] shadow-md overflow-hidden">
                <!-- Image or Placeholder -->
                <?php if ($imageUrl): // Check if a real image URL was returned ?>
                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>" class="staff-image w-full aspect-square object-cover object-top">
                <?php else: ?>
                    <!-- Placeholder Styling: bg color, text color, flex center -->
                    <div class="staff-image w-full aspect-square flex items-center justify-center text-4xl font-bold" style="background-color: <?php echo $placeholderColor; ?>; color: <?php echo $placeholderTextColor; ?>;">
                        <?php echo $placeholderInitials; ?>
                    </div>
                <?php endif; ?>

                <!-- Details Styling: padding, flex col, h-full -->
                <div class="staff-details relative p-4 flex flex-col h-full" style="--dept-color: <?php echo htmlspecialchars($staff['department_color']); ?>">
                    <!-- Company Styling: flex, items-center, mb, text size, color -->
                    <?php if (!empty($staff['company'])): ?>
                    <div class="staff-company flex items-center mb-1">
                        <?php if (!empty($staff['company_logo'])): ?>
                            <img src="<?php echo url(htmlspecialchars($staff['company_logo'])); ?>" alt="<?php echo htmlspecialchars($staff['company']); ?> logo" class="company-logo max-h-6 max-w-12 mr-2 object-contain">
                        <?php endif; ?>
                        <span class="company-name text-sm text-gray-500 font-light"><?php echo htmlspecialchars($staff['company']); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Name Styling: size, weight, color, margin -->
                    <h3 class="staff-name text-lg font-medium text-gray-800 mt-1"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h3>
                    <!-- Job Styling: size, color -->
                    <p class="staff-job text-sm dark-text"><?php echo htmlspecialchars($staff['job_title']); ?></p>
                    <!-- Department Styling: margin -->
                    <p class="staff-department mt-2 mb-2">
                        <?php
                        // Get proper text color contrast class
                        $text_class = get_text_contrast_class($staff['department_color']);
                        ?>
                        <!-- Pill Styling: inline-block, padding, rounded, text size, font weight -->
                        <span class="pill inline-block px-2.5 py-0.5 rounded-full  <?php echo $text_class; ?> text-xs font-medium" style="background-color: <?php echo htmlspecialchars($staff['department_color']); ?>">
                            <?php echo htmlspecialchars($staff['department']); ?>
                        </span>
                    </p>
                    <!-- Email Styling: size, color, margin, flex, gap, hover -->
                    <p class="staff-email text-xs mt-auto flex items-center gap-1 dark-text">
                        <i class="ri-mail-line text-xs"></i>
                        <a href="mailto:<?php echo htmlspecialchars($staff['email']); ?>"><?php echo htmlspecialchars($staff['email']); ?></a>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="col-span-full text-center text-gray-500"><?php echo __('no_staff_found'); ?></p> <?php // Added styling for no results ?>
    <?php endif; ?>
</div>
<script>
'use  strict'
document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration Constants ---
    const VISIBLE_CLASS_NAME = 'card-visible' // CSS class for the visible state
    const ANIMATE_SELECTOR = '.staff-card' // CSS selector for elements to animate
    const ANIMATION_DELAY = 65 // Base delay (ms) within an animation batch
    const BATCH_THRESHOLD = 20 // Time threshold (ms) to define a new batch
    const OBSERVER_THRESHOLD = 0.35 // Intersection Observer threshold (0.0 to 1.0)

    // --- Scroll Animation Initialization ---
    if(document.querySelectorAll(ANIMATE_SELECTOR).length > 0) {
        // Initialize the ScrollAnimator first
        const scrollAnimator = new ScrollAnimator({
            visibleClass: VISIBLE_CLASS_NAME,
            selector: ANIMATE_SELECTOR,
            delay: ANIMATION_DELAY,
            batchThreshold: BATCH_THRESHOLD,
            threshold: OBSERVER_THRESHOLD
        })

        // Then reveal elements above the viewport
        scrollAnimator.revealAboveViewportOnLoad()
    }
})

</script>
<script src="assets/js/frontend-filters.js"></script>

<?php require_once 'includes/footer.php'; ?>
