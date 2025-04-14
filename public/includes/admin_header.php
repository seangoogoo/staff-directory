<?php
// This file should be included after admin_head.php
// It assumes $app_settings has already been set
if (!isset($app_settings)) {
    die('Error: This file must be included after admin_head.php');
}

// Get admin title from settings
$admin_title = $app_settings['admin_title']; // Default is already provided by load_app_settings
?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_title); ?></title>
    <link rel="icon" href="<?php echo asset('favicon.ico'); ?>">
    <link href="<?php echo asset('css/styles.css'); ?>" rel="stylesheet">
    <!-- Make APP_BASE_URI and translations available to JavaScript -->
    <script>
        window.APP_BASE_URI = "<?php echo APP_BASE_URI; ?>"
        window.currentLanguage = "<?php echo current_language(); ?>"
        <?php if ($_ENV['DEV_MODE'] == 'true') { ?>
        window.DEV_MODE = true
        <?php } ?>

        // Add translations for JavaScript
        window.translations = {
            // Common translations
            'all_departments': "<?php echo __('all_departments'); ?>",
            'all_companies': "<?php echo __('all_companies'); ?>",
            'no_staff_found': "<?php echo __('no_staff_found'); ?>",
            'search': "<?php echo __('search'); ?>",
            'filter': "<?php echo __('filter'); ?>",
            'sort': "<?php echo __('sort'); ?>",
            'loading': "<?php echo __('loading'); ?>",
            'error': "<?php echo __('error'); ?>",
            'success': "<?php echo __('success'); ?>"
        };
    </script>
    <script src="<?php echo asset('js/i18n.js'); ?>"></script>
    <script src="<?php echo asset('js/breakpoints.js'); ?>"></script>
    <script src="<?php echo asset('js/main.js'); ?>"></script>
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
                $logo_path = !empty($app_settings['custom_logo_path']) ? url($app_settings['custom_logo_path']) : url('assets/images/staff-directory-logo.svg');
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
                        <a href="<?php echo url('admin/index.php'); ?>" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-id-card-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('staff_management'); ?></span><span class="hidden nav:inline"><?php echo __('staff_management'); ?></span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="<?php echo url('admin/departments.php'); ?>" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-group-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('department_management'); ?></span><span class="hidden nav:inline"><?php echo __('department_management'); ?></span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="<?php echo url('admin/companies.php'); ?>" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-building-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('company_management'); ?></span><span class="hidden nav:inline"><?php echo __('company_management'); ?></span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="<?php echo url('admin/settings.php'); ?>" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-equalizer-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('settings'); ?></span><span class="hidden nav:inline"><?php echo __('settings'); ?></span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="<?php echo url(''); ?>" target="_blank" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-eye-2-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('home'); ?></span><span class="hidden nav:inline"><?php echo __('home'); ?></span>
                        </a>
                    </li>
                    <li class="w-full nav:w-auto">
                        <a href="<?php echo url('admin/auth/logout.php'); ?>" class="icon-link flex items-center h-10 px-3 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200 w-full nav:w-auto justify-start nav:justify-center">
                            <i class="ri-logout-box-line mr-2 nav:mr-1"></i> <span class="nav:hidden inline"><?php echo __('logout'); ?></span><span class="hidden nav:inline"><?php echo __('logout'); ?></span>
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
            // Use the Breakpoints utility to get the nav breakpoint value from CSS
            if (Breakpoints.below('nav')) {
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

            if (!isClickInsideNav && !isClickOnButton && !adminNav.classList.contains('hidden') && Breakpoints.below('nav')) {
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
