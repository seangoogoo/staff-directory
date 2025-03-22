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
    <title>Admin - Staff Directory</title>
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
                <img src="/assets/images/staff-directory-logo.svg" alt="Staff Directory Logo" class="site-logo">
                <span class="admin-title">Staff Directory Admin</span>
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
