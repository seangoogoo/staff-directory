<?php
/**
 * Logout handler
 */

// Include bootstrap to ensure constants are defined
require_once __DIR__ . '/../../includes/bootstrap.php';

// Send cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT"); // Set expiration in the past

// Start session first to ensure consistency
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once __DIR__ . '/auth.php';

// Store the username for logout message (optional)
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Log the user out
logout_user();

// Clear any output before redirect
ob_clean();

// Wait a moment to ensure session is cleared
usleep(10000); // 10ms pause

// Redirect to the homepage with a logout message
header("Location: " . url('') . "?logout=success&t=" . time());
exit;
