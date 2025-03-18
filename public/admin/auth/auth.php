<?php
/**
 * Authentication System Core
 *
 * This file manages login verification, session handling, and access control.
 * It uses centralized configuration from auth_config.php for all settings.
 */

// Define constants to prevent direct access to configuration file
define('AUTH_SYSTEM', true);

// Load centralized authentication configuration
require_once __DIR__ . '/auth_config.php';

// Only set cookie parameters if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters before starting session
    // These settings help ensure cookies work across the entire domain
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"], // Keep default lifetime
        COOKIE_PATH,             // From config: Make cookie available for the entire domain
        $cookieParams["domain"], // Keep default domain
        USE_SECURE_COOKIES,      // From config: Secure if HTTPS
        true                     // HttpOnly for security
    );

    // Start the session
    session_start();
} else if (session_status() === PHP_SESSION_DISABLED) {
    // Sessions are disabled
    error_log("Sessions are disabled on this server");
}

/**
 * Check if the user is logged in with improved session validation
 *
 * @return bool True if the user is logged in, false otherwise
 */
function is_logged_in() {
    // First check if session exists and has required data
    if (!isset($_SESSION) || empty($_SESSION)) {
        return false;
    }
    
    // Check for session expiration
    if (isset($_SESSION['login_time'])) {
        // Session expired after configured lifetime
        if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
            logout_user();
            return false;
        }
        
        // Update login time occasionally to extend session for active users
        if (time() - $_SESSION['login_time'] > SESSION_UPDATE_INTERVAL) {
            $_SESSION['login_time'] = time();
        }
    } else {
        // No login time set, session is invalid
        return false;
    }
    
    // Check for required login flags
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        return false;
    }
    
    // Check for username
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        return false;
    }
    
    // Check auth token and user agent for session hijacking prevention
    if (isset($_SESSION['auth_token']) && isset($_SESSION['user_agent'])) {
        // Verify the auth_check cookie matches our expected value
        if (isset($_COOKIE['auth_check'])) {
            $expected_auth = hash('sha256', $_SESSION['auth_token'] . $_SERVER['HTTP_USER_AGENT']);
            if ($_COOKIE['auth_check'] !== $expected_auth) {
                // Auth check failed - possible session hijacking
                logout_user();
                return false;
            }
        } else {
            // Cookie missing, logout for security
            logout_user();
            return false;
        }
        
        // Verify user agent hasn't changed (basic check)
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            logout_user();
            return false;
        }
    }
    
    return true;
}

/**
 * Verify login credentials with improved security
 *
 * @param string $username Username to verify
 * @param string $password Password to verify
 * @return bool True if credentials are valid, false otherwise
 */
function verify_login($username, $password) {
    // Check credentials against configured values from auth_config.php
    if ($username === ADMIN_USERNAME) {
        if (password_verify($password, ADMIN_PASSWORD_HASH)) {
            return true;
        }
    }
    return false;
}

/**
 * Set a user as logged in with enhanced security
 *
 * @param string $username Username to set as logged in
 */
function login_user($username) {
    // Clear any existing session data
    $_SESSION = array();

    // Set login information
    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    
    // Add additional security information
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['auth_token'] = bin2hex(random_bytes(32)); // Unique token for this session

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set a cookie with configured lifetime - but use session verification for actual auth
    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), time() + COOKIE_LIFETIME,
        COOKIE_PATH, '', USE_SECURE_COOKIES, true);
        
    // Set a secondary auth cookie for added verification
    setcookie('auth_check', hash('sha256', $_SESSION['auth_token'] . $_SERVER['HTTP_USER_AGENT']), 
        time() + COOKIE_LIFETIME, COOKIE_PATH, '', USE_SECURE_COOKIES, true);
}

/**
 * Log the user out with thorough cleanup
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = array();

    // Delete the session cookie with root path to ensure it's removed site-wide
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        
        // Clear the session cookie with explicit path
        setcookie(session_name(), '', time() - 42000, COOKIE_PATH, '', false, true);
        
        // Also clear it with the original parameters
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        
        // Clear any other potential authentication cookies
        setcookie('auth_check', '', time() - 42000, COOKIE_PATH, '', false, true);
    }
    
    // Destroy the session
    session_destroy();
    
    // Force PHP to use a new session ID for the next session
    if (session_status() !== PHP_SESSION_DISABLED) {
        session_start();
        session_regenerate_id(true);
        session_destroy();
    }
}

/**
 * Streamlined login requirement that integrates with the new modal approach
 * This is the main security gate for all admin pages
 * 
 * @param bool $ajax Whether the request is AJAX (returns JSON instead of redirecting)
 */
function require_login($ajax = false) {
    // First check if we should be logged in
    if (!is_logged_in()) {
        // Clear any potentially invalid sessions
        logout_user();
        
        // For AJAX requests, return JSON
        if ($ajax || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'logged_in' => false,
                'redirect' => '/?' . LOGIN_TRIGGER_PARAM . '&return=' . urlencode($_SERVER['REQUEST_URI'])
            ]);
            exit;
        }
        
        // Add cache control headers to prevent caching
        header("Cache-Control: " . CACHE_CONTROL_HEADER);
        header("Pragma: " . PRAGMA_HEADER);
        
        // Set a session flag to show the login modal
        $_SESSION[LOGIN_MODAL_FLAG] = true;
        
        // Redirect to the homepage with login required flag
        $return_url = urlencode($_SERVER['REQUEST_URI']);
        header("Location: /?" . LOGIN_TRIGGER_PARAM . "&return=" . $return_url);
        exit;
    }
    
    // For added security, add cache control headers
    header("Cache-Control: " . CACHE_CONTROL_HEADER);
    header("Pragma: " . PRAGMA_HEADER);
    
    // Update the authentication timestamp
    if (isset($_SESSION['login_time'])) {
        $_SESSION['last_activity'] = time();
    }
}
