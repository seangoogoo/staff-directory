<?php
/**
 * Login handler for AJAX requests
 * Uses centralized configuration from auth_config.php
 */

// Prevent any output before our JSON
ob_start();

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

try {
    // Include auth functions and configuration
    require_once __DIR__ . '/auth.php';

    // Clear any previous output
    ob_end_clean();

    // Set JSON content type
    header('Content-Type: application/json');

    // Process only POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Check if data is provided
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get the return URL from POST or use the default
    $returnUrl = isset($_POST['returnUrl']) ? $_POST['returnUrl'] : DEFAULT_RETURN_URL;

    // Ensure the return URL has the APP_BASE_URI prefix if it's a relative URL
    if (!empty($returnUrl) && $returnUrl[0] === '/' && !empty(APP_BASE_URI)) {
        // Only add APP_BASE_URI if it's not empty and the URL is relative
        if (strpos($returnUrl, 'http') !== 0 && strpos($returnUrl, APP_BASE_URI) !== 0) {
            $returnUrl = APP_BASE_URI . $returnUrl;
        }
    }

    // Verify credentials
    if (verify_login($username, $password)) {
        // Login successful
        login_user($username);

        // Return success response with the return URL
        echo json_encode(['success' => true, 'returnUrl' => $returnUrl]);
    } else {
        // Login failed
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (Exception $e) {
    // If any error occurs, still return valid JSON
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: ' . CACHE_CONTROL_HEADER);
    header('Pragma: ' . PRAGMA_HEADER);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
exit;
