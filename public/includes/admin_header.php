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
    <link rel="stylesheet" href="/assets/vendor/lineicons/lineicons.css">
    <?php
    // Add timestamp to CSS URL in dev mode to force cache refresh
    $css_url = "/assets/css/admin.css";
    if (isset($_ENV['DEV_MODE']) && $_ENV['DEV_MODE'] === 'true') {
        $css_url .= "?v=" . time();
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_url; ?>">
</head>
<body class="admin-area">
    <header class="admin-header">
        <div class="container">
            <div class="site-branding">
                <?php
                // Use the app settings we already loaded above
                $logo_path = !empty($app_settings['custom_logo_path']) ? $app_settings['custom_logo_path'] : '/assets/images/staff-directory-logo.svg';
                // admin_title is already set above
                $show_logo = isset($app_settings['show_logo']) ? $app_settings['show_logo'] : '1';

                // Only show the logo if the setting is enabled
                if ($show_logo === '1') :
                ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($admin_title); ?> Logo" class="site-logo">
                <?php endif; ?>
                <span class="site-title dark-text"><?php echo htmlspecialchars($admin_title); ?></span>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="/admin/index.php">Manage staff members</a></li>
                    <li><a href="/admin/departments.php">Manage departments</a></li>
                    <li><a href="/admin/settings.php">Settings</a></li>
                    <li><a href="/" target="_blank">View Front-end</a></li>
                    <li><a href="/admin/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container admin-container">
