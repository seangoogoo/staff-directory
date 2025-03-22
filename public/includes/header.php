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
    <title>Staff Directory</title>
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
                <img src="/assets/images/staff-directory-logo.svg" alt="Staff Directory Logo" class="site-logo">
                <span class="site-title">Staff Directory</span>
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
