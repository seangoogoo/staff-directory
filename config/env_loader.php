<?php
/**
 * Environment Variable Loader
 *
 * Loads environment variables from a .env file located outside the web root
 * for improved security
 */

/**
 * Load environment variables from a .env file
 *
 * @param string $path Path to the .env file
 * @return array Associative array of loaded environment variables
 */
function load_env($path) {
    $env_vars = [];

    // Check if file exists
    if (!file_exists($path)) {
        error_log("Environment file not found: $path");
        return $env_vars;
    }

    // Read the file line by line
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        error_log("Failed to read environment file: $path");
        return $env_vars;
    }

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse environment variables
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove surrounding quotes if any
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            // Set environment variable
            $env_vars[$name] = $value;

            // Also set as actual environment variable
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }

    return $env_vars;
}

// Load environment variables from file outside the web root
// The env folder is now directly in the project root, at the same level as config
$env_path = dirname(__DIR__) . '/staff_dir_env/.env';
$env_vars = load_env($env_path);

// Debug line to help diagnose errors
// if ($_ENV['DEV_MODE'] == 'true') {
//     error_log("Trying to load env file from: {$env_path} - File exists: " . (file_exists($env_path) ? 'Yes' : 'No'));
// }
