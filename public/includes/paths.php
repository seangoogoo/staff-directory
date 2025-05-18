<?php
/**
 * Staff Directory Path Configuration
 * 
 * This file defines all path constants used throughout the application.
 * It is the single source of truth for path definitions and is used by
 * both the installer and the main application.
 */

// Define base paths if not already defined
if (!defined('BASE_PATH')) {
    // Project root is two levels up from the includes directory
    // /project-root/public/staff-directory/includes -> /project-root
    define('BASE_PATH', dirname(__DIR__, 2)); // Project root
    define('PRIVATE_PATH', BASE_PATH); // Private files directory (same as BASE_PATH in this setup)
    define('PUBLIC_PATH', BASE_PATH . '/public'); // Path to the public directory
    define('APP_BASE_URI', ''); // The base URI for routing
}

// Allow for path overrides in a local configuration file
$local_paths_file = __DIR__ . '/paths.local.php';
if (file_exists($local_paths_file)) {
    include $local_paths_file;
}

// Add debug logging if in development mode
if (isset($_ENV['DEV_MODE']) && $_ENV['DEV_MODE'] === 'true') {
    error_log('Path Configuration:');
    error_log('BASE_PATH: ' . BASE_PATH);
    error_log('PRIVATE_PATH: ' . PRIVATE_PATH);
    error_log('PUBLIC_PATH: ' . PUBLIC_PATH);
    error_log('APP_BASE_URI: ' . APP_BASE_URI);
}
