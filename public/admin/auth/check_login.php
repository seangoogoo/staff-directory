<?php
/**
 * Login status check for AJAX requests
 * Uses centralized configuration from auth_config.php
 */

// Include bootstrap to ensure constants are defined
require_once __DIR__ . '/../../includes/bootstrap.php';

// Start session first to ensure consistency
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant to prevent direct access to configuration
if (!defined('AUTH_SYSTEM')) {
    define('AUTH_SYSTEM', true);
}

// Include auth functions and configuration
require_once __DIR__ . '/auth.php';

// Set JSON content type
header('Content-Type: application/json');

// Add cache control headers to prevent caching
header('Cache-Control: ' . CACHE_CONTROL_HEADER);
header('Pragma: ' . PRAGMA_HEADER);

// Check login status and return as JSON
$logged_in = is_logged_in();

// Simplified response - only what's needed
echo json_encode([
    'logged_in' => $logged_in,
    'timestamp' => time()
]);
exit;
