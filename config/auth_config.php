<?php
/**
 * Authentication Configuration
 *
 * This file centralizes all authentication-related settings and constants
 * for easier maintenance and configuration.
 */

// Make sure this file is not accessed directly
if (!defined('AUTH_SYSTEM')) {
    die('Direct access not permitted');
}

// Load environment variables if not already loaded
if (!isset($_ENV['ADMIN_USERNAME'])) {
    require_once __DIR__ . '/env_loader.php';
}

// =============================================
// Session Configuration
// =============================================

// Session lifetime in seconds (24 hours)
define('SESSION_LIFETIME', isset($_ENV['SESSION_LIFETIME']) ? (int)$_ENV['SESSION_LIFETIME'] : 86400);

// Session activity check interval (1 hour)
define('SESSION_UPDATE_INTERVAL', isset($_ENV['SESSION_UPDATE_INTERVAL']) ? (int)$_ENV['SESSION_UPDATE_INTERVAL'] : 3600);

// Path for cookies (site-wide)
define('COOKIE_PATH', isset($_ENV['COOKIE_PATH']) ? $_ENV['COOKIE_PATH'] : '/');

// Cookie lifetime in seconds (30 days)
define('COOKIE_LIFETIME', isset($_ENV['COOKIE_LIFETIME']) ? (int)$_ENV['COOKIE_LIFETIME'] : 2592000);

// =============================================
// Security Settings
// =============================================

// Password hashing algorithm
define('PASSWORD_ALGO', PASSWORD_DEFAULT);

/**
 * Controls whether to use the 'secure' flag for cookies
 *
 * Can be configured three ways:
 * 1. Set USE_SECURE_COOKIES=true in .env file to always use secure cookies
 * 2. Set USE_SECURE_COOKIES=false in .env file to never use secure cookies
 * 3. Don't set it in .env file to auto-detect based on HTTPS
 *
 * Production environments should typically use secure cookies
 */
define('USE_SECURE_COOKIES',
    isset($_ENV['USE_SECURE_COOKIES'])
        ? (strtolower($_ENV['USE_SECURE_COOKIES']) === 'true')
        : isset($_SERVER['HTTPS'])
);

// User credential constants
define('ADMIN_USERNAME', isset($_ENV['ADMIN_USERNAME']) ? $_ENV['ADMIN_USERNAME'] : 'admin');

// Admin password hash, read from .env. It is NEVER computed here: doing it on every
// request cost ~180 ms per page load under PHP 8.4 (bcrypt cost 12) and protected
// nothing, since the hash was derived from the expected password itself and thrown
// away — password_verify() against it was a string comparison in disguise.
//
// Produce one with:
//   php -r 'echo password_hash("your-password", PASSWORD_DEFAULT), PHP_EOL;'
// or let public/install.php write it. Existing installations migrate with
// tools/hash_admin_password.php. An empty value means no admin can log in.
define('ADMIN_PASSWORD_HASH', isset($_ENV['ADMIN_PASSWORD_HASH']) ? $_ENV['ADMIN_PASSWORD_HASH'] : '');

// =============================================
// Login Behavior
// =============================================

// Default return URL after login
// Include APP_BASE_URI if it's defined, otherwise use a relative path
define('DEFAULT_RETURN_URL', defined('APP_BASE_URI') ? APP_BASE_URI . '/admin/index.php' : '/staff-directory/admin/index.php');

// URL param to trigger login modal
define('LOGIN_TRIGGER_PARAM', 'login=required');

// Session flag name to indicate modal should show
define('LOGIN_MODAL_FLAG', 'show_login');

// =============================================
// Cache Control
// =============================================

// Standard cache control header for authenticated pages
define('CACHE_CONTROL_HEADER', 'no-store, no-cache, must-revalidate, max-age=0');

// Pragma header value
define('PRAGMA_HEADER', 'no-cache');
