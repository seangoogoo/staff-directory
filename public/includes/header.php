<?php
require_once __DIR__ . '/bootstrap.php';
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
    $frontend_title = $app_settings['frontend_title']; // Default is already provided by load_app_settings
    ?>
    <title><?php echo htmlspecialchars($frontend_title); ?></title>
    <link href="<?php echo asset('css/styles.css'); ?>" rel="stylesheet">
    <!-- Make APP_BASE_URI available to JavaScript -->
    <script>
        window.APP_BASE_URI = "<?php echo APP_BASE_URI; ?>"
        <?php if ($_ENV['DEV_MODE'] == 'true') { ?>
        window.DEV_MODE = true
        <?php } ?>
    </script>
    <!-- Core filter module shared between frontend and admin -->
    <script src="<?php echo asset('js/filter-core.js'); ?>"></script>
</head>
<!-- Added flex for sticky footer -->
<body class="antialiased font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">
    <!-- Header Styling: bg-gray-100, py-4 -->
    <header class="main-header bg-gray-100 py-4">
        <!-- Container Styling: max-w-screen-xl, mx-auto, px-4, md:flex -->
        <div class="container w-full max-w-screen-xl mx-auto px-4 md:flex md:items-center md:justify-between">
            <!-- Site Branding Styling: flex, items-center, gap-4 -->
            <div class="site-branding flex items-center gap-4">
                <?php
                // Use the app settings we already loaded above
                $logo_path = !empty($app_settings['custom_logo_path']) ? $app_settings['custom_logo_path'] : url('assets/images/staff-directory-logo.svg');
                $show_logo = isset($app_settings['show_logo']) ? $app_settings['show_logo'] : '1';

                // Only show the logo if the setting is enabled
                if ($show_logo === '1') :
                ?>
                <!-- Logo Styling: h-10 -->
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($frontend_title); ?> Logo" class="site-logo h-10 w-auto">
                <?php endif; ?>
                <!-- Title Styling: text-3xl, font-thin, text-gray-800 -->
                <span class="site-title text-3xl font-thin text-gray-800"><?php echo htmlspecialchars($frontend_title); ?></span>
            </div>
            <nav class="main-nav">
                <!-- Nav UL Styling: flex, mobile margin, md margin reset -->
                <ul class="flex my-4 md:my-0 p-0 list-none">
                    <li>
                        <!-- Icon Link Styling: flex center, size, rounded, border, text color, hover, transition -->
                        <a href="#" id="adminLink" class="icon-link flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-colors duration-200" title="Admin Area">
                            <!-- Use RemixIcon for login -->
                            <i class="ri-login-box-line text-lg"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <?php
    // Include the consolidated login modal (path remains the same)
    require_once __DIR__ . '/../admin/auth/login-modal.php';
    ?>

    <!-- Main Container Styling: py-8, standard container, flex-grow -->
    <main class="container w-full max-w-screen-xl mx-auto px-4 py-8 flex-grow">
