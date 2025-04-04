<?php
// Include authentication system first (handles session start and auth check)
require_once __DIR__ . '/../admin/auth/auth.php';

// Include other requirements after auth check
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Send cache control headers before any output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in, redirect to login if not
// Note: require_login() will handle its own headers
if (!is_logged_in()) {
    require_login();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Get application settings with defaults - make them globally accessible
    global $app_settings;
    $app_settings = load_app_settings();
    $admin_title = $app_settings['admin_title']; // Default is already provided by load_app_settings
    ?>
    <title><?php echo htmlspecialchars($admin_title); ?></title>
    <link href="/assets/css/styles.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
    <script src="assets/js/main.js"></script>
</head>
<body class="antialiased font-sans bg-gray-100 text-gray-900 flex flex-col min-h-screen">
    <!-- Admin Header: White background, padding, border, flex layout -->
    <header class="admin-header bg-white shadow-sm py-3 px-4 border-b border-gray-200">
        <!-- Container: Standard width, centered, flex for layout -->
        <div class="container w-full max-w-screen-xl mx-auto flex items-center justify-between">
            <!-- Site Branding: Flex, logo height, title -->
            <div class="site-branding flex items-center gap-3">
                <?php
                // Use the app settings we already loaded above
                $logo_path = !empty($app_settings['custom_logo_path']) ? $app_settings['custom_logo_path'] : '/assets/images/staff-directory-logo.svg';
                // admin_title is already set above
                $show_logo = isset($app_settings['show_logo']) ? $app_settings['show_logo'] : '1';

                // Only show the logo if the setting is enabled
                if ($show_logo === '1') :
                ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($admin_title); ?> Logo" class="h-8 w-auto">
                <?php endif; ?>
                <span class="site-title text-3xl font-thin text-gray-800"><?php echo htmlspecialchars($admin_title); ?></span>
            </div>

            <!-- Mobile Menu Button (visible below nav breakpoint) -->
            <button id="mobile-menu-button" class="block nav:hidden h-10 w-10 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200">
                <i class="ri-menu-line text-xl"></i>
            </button>

            <!-- Admin Navigation: Flex container for links -->
            <nav class="admin-nav hidden nav:block absolute nav:relative top-16 right-4 nav:top-auto nav:right-auto bg-white nav:bg-transparent shadow-md nav:shadow-none rounded-lg nav:rounded-none border border-gray-200 nav:border-0 z-10 w-auto">
                <!-- Link styling: flex, gap, text size -->
                <ul class="flex flex-col nav:flex-row gap-1 md:gap-2 list-none p-2 nav:p-0 m-0 text-sm">
                    <li class="w-full nav:w-auto">
                        <a href="/admin/index.php" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-id-card-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">Manage Staff</span><span class="hidden nav:inline">Manage Staff</span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="/admin/departments.php" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-group-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">Departments</span><span class="hidden nav:inline">Departments</span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="/admin/companies.php" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-building-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">Companies</span><span class="hidden nav:inline">Companies</span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="/admin/settings.php" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-equalizer-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">Settings</span><span class="hidden nav:inline">Settings</span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="/" target="_blank" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-eye-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">View Site</span><span class="hidden nav:inline">View Site</span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="/admin/auth/logout.php" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-logout-box-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline">Logout</span><span class="hidden nav:inline">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Add JavaScript for mobile menu toggle -->
    <script>
    'use strict'
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button')
        const adminNav = document.querySelector('.admin-nav')

        // Function to check screen size and set appropriate menu state
        function checkScreenSize() {
            if (window.innerWidth < 1180) {
                // When below nav breakpoint, hide the menu
                adminNav.classList.add('hidden')
            } else {
                // When above nav breakpoint, show the menu
                adminNav.classList.remove('hidden')
            }
        }

        // Run on initial load
        checkScreenSize()

        // Toggle mobile menu when button is clicked
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', function() {
                adminNav.classList.toggle('hidden')
            })
        }

        // Close menu if clicked outside
        document.addEventListener('click', function(event) {
            const isClickInsideNav = adminNav.contains(event.target)
            const isClickOnButton = mobileMenuButton.contains(event.target)

            if (!isClickInsideNav && !isClickOnButton && !adminNav.classList.contains('hidden') && window.innerWidth < 1180) {
                adminNav.classList.add('hidden')
            }
        })

        // Handle resize events
        window.addEventListener('resize', function() {
            checkScreenSize()
        })
    })
    </script>

    <!-- Main Container: Padding + flex-grow -->
    <main class="container w-full max-w-screen-xl mx-auto px-4 py-6 flex-grow">
