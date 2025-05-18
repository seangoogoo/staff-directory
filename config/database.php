<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database Configuration using environment variables
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
define('DB_USER', isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '');
define('DB_PASS', isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '');
define('DB_NAME', isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'staff_dir');
define('DB_CREATE_DATABASE', isset($_ENV['DB_CREATE_DATABASE']) ?
    (strtolower($_ENV['DB_CREATE_DATABASE']) === 'true') : true);

// Define table prefix from environment variable or use empty string as default
define('DB_TABLE_PREFIX', isset($_ENV['DB_TABLE_PREFIX']) ? $_ENV['DB_TABLE_PREFIX'] : '');

// Define table names with prefixes
define('TABLE_COMPANIES', DB_TABLE_PREFIX . 'companies');
define('TABLE_DEPARTMENTS', DB_TABLE_PREFIX . 'departments');
define('TABLE_STAFF_MEMBERS', DB_TABLE_PREFIX . 'staff_members');
define('TABLE_APP_SETTINGS', DB_TABLE_PREFIX . 'app_settings');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist and DB_CREATE_DATABASE is true
if (DB_CREATE_DATABASE) {
    $sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`";
    if ($conn->query($sql) !== TRUE) {
        die("Error creating database: " . $conn->error);
    }
}

// Select the database
$conn->select_db(DB_NAME);

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

/**
 * Helper function to get the prefixed table name
 *
 * This function can be used as an alternative to constants if needed
 *
 * @param string $table_name The base table name without prefix
 * @return string The table name with prefix applied
 */
function get_table_name($table_name) {
    return DB_TABLE_PREFIX . $table_name;
}
?>
