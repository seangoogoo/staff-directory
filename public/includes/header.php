<?php
// Start session first to avoid header warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth system first to setup authentication constants
require_once __DIR__ . '/../admin/auth/auth.php';

// Include other required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
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
    <link rel="stylesheet" href="/assets/vendor/lineicons/lineicons.css">
    <?php
    // Add timestamp to CSS URL in dev mode to force cache refresh
    $css_url = "/assets/css/frontend.css";
    if (isset($_ENV['DEV_MODE']) && $_ENV['DEV_MODE'] === 'true') {
        $css_url .= "?v=" . time();
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_url; ?>">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="site-branding">
                <?php
                // Use the app settings we already loaded above
                $logo_path = !empty($app_settings['custom_logo_path']) ? $app_settings['custom_logo_path'] : '/assets/images/staff-directory-logo.svg';
                $site_title = $app_settings['frontend_title']; // Already has default from load_app_settings
                $show_logo = isset($app_settings['show_logo']) ? $app_settings['show_logo'] : '1';

                // Only show the logo if the setting is enabled
                if ($show_logo === '1') :
                ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($site_title); ?> Logo" class="site-logo">
                <?php endif; ?>
                <span class="site-title dark-text"><?php echo htmlspecialchars($site_title); ?></span>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="#" id="adminLink" class="icon-link" title="Admin Area"><i class="lni lni-user-4"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <?php
    // Include the consolidated login modal
    require_once __DIR__ . '/../admin/auth/login-modal.php';
    ?>

    <main class="container">
