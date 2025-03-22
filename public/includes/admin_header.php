<?php
// Include authentication system first (handles session start and auth check)
require_once __DIR__ . '/../admin/auth/auth.php';

// Include other requirements after auth check
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in, redirect to login if not
require_login();
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
            <span class="admin-title">Admin</span>
            <nav class="admin-nav">
                <ul>
                    <li><a href="/admin/index.php">Manage staff members</a></li>
                    <li><a href="/admin/departments.php">Manage departments</a></li>
                    <li><a href="/" target="_blank">View Front-end</a></li>
                    <li><a href="/admin/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container admin-container">
