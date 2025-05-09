<?php
// Prevent direct access
if (!defined('INCLUDED_FROM_ADMIN_PAGE')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

require_once __DIR__ . '/bootstrap.php';

// Send cache control headers before any output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in, redirect to login if not
if (!is_logged_in()) {
    require_login();
}

// Get application settings with defaults - make them globally accessible
global $app_settings;
$app_settings = load_app_settings();
?>
