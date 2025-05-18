<?php
/**
 * SQL Processor Script
 *
 * This script processes SQL files and replaces placeholders with actual values:
 * - {DB_NAME} with the database name
 * - {PREFIX} with the table prefix
 * - Conditionally includes or excludes {DB_CREATE} sections
 */

// Load environment variables
require_once __DIR__ . '/../config/env_loader.php';

// Define database name and table prefix
// First check if values were passed via putenv (from install.php)
$db_name_env = getenv('DB_NAME');
$prefix_env = getenv('DB_TABLE_PREFIX');
$create_db_env = getenv('DB_CREATE_DATABASE');

// If not set via putenv, fall back to $_ENV (from .env file)
$db_name_default = $db_name_env !== false ? $db_name_env : (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'staff_dir');
$prefix_default = $prefix_env !== false ? $prefix_env : (isset($_ENV['DB_TABLE_PREFIX']) ? $_ENV['DB_TABLE_PREFIX'] : '');
$create_db_default = $create_db_env !== false ?
    (strtolower($create_db_env) === 'true') :
    (isset($_ENV['DB_CREATE_DATABASE']) ? (strtolower($_ENV['DB_CREATE_DATABASE']) === 'true') : true);

// Debug log
error_log("Default values from environment: DB_NAME=$db_name_default, DB_TABLE_PREFIX=$prefix_default, DB_CREATE_DATABASE=" . ($create_db_default ? 'true' : 'false'));

/**
 * Process SQL file by replacing placeholders
 *
 * @param string $input_file Path to input SQL file
 * @param string $output_file Path to output SQL file (optional)
 * @param string $db_name Database name to use
 * @param string $prefix Table prefix to use
 * @param bool $create_db Whether to include database creation statements
 * @return string|bool Path to processed file or false on failure
 */
function process_sql_file($input_file, $output_file = null, $db_name = 'staff_dir', $prefix = '', $create_db = true) {
    // If no output file specified, use temporary file
    $output_file = $output_file ?: $input_file . '.tmp';

    // Ensure prefix is properly formatted
    if ($prefix !== '' && substr($prefix, -1) !== '_') {
        $prefix .= '_';
        error_log("Added underscore to prefix: $prefix");
    }

    // Always use the parameters passed to this function, not the global variables
    // This ensures that the values from install.php are used, not from .env
    error_log("process_sql_file called with parameters: db_name=$db_name, prefix=$prefix");

    // Read input file
    $sql = file_get_contents($input_file);
    if ($sql === false) {
        echo "Error: Could not read input file: $input_file\n";
        return false;
    }

    // Process DB_CREATE sections
    if (!$create_db) {
        // Remove DB_CREATE sections if we shouldn't create the database
        $sql = preg_replace('/-- {DB_CREATE}.*?-- {\/DB_CREATE}/s', '', $sql);
    } else {
        // Keep the content but remove the markers
        $sql = str_replace(['-- {DB_CREATE}', '-- {/DB_CREATE}'], '', $sql);
    }

    // Replace placeholders
    // Debug: Log the values being used for replacement
    error_log("Replacing placeholders: DB_NAME=$db_name, PREFIX=$prefix");

    // Ensure prefix is properly handled
    $prefix_value = (is_string($prefix) && $prefix !== '') ? $prefix : '';

    // Make sure the prefix ends with an underscore
    if ($prefix_value !== '' && substr($prefix_value, -1) !== '_') {
        $prefix_value .= '_';
        error_log("Added underscore to prefix_value: $prefix_value");
    }

    $processed_sql = str_replace(
        ['{DB_NAME}', '{PREFIX}'],
        [$db_name, $prefix_value],
        $sql
    );

    // Verify that the prefix was properly applied
    $sample_table = "CREATE TABLE IF NOT EXISTS `" . $prefix_value . "companies`";
    if (strpos($processed_sql, $sample_table) === false) {
        error_log("WARNING: Prefix not properly applied in SQL. Expected: $sample_table");
        error_log("First 500 chars of processed SQL: " . substr($processed_sql, 0, 500));
    } else {
        error_log("Prefix successfully applied in SQL: $sample_table");
    }

    // Write to output file
    if (file_put_contents($output_file, $processed_sql) === false) {
        echo "Error: Could not write to output file: $output_file\n";
        return false;
    }

    return $output_file;
}

/**
 * Execute SQL file on database
 *
 * @param string $sql_file Path to SQL file to execute
 * @param string $db_host Database host
 * @param string $db_user Database username
 * @param string $db_pass Database password
 * @return bool True on success, false on failure
 */
function execute_sql_file($sql_file, $db_host = 'localhost', $db_user = '', $db_pass = '') {
    // Check if file exists
    if (!file_exists($sql_file)) {
        echo "Error: SQL file not found: $sql_file\n";
        return false;
    }

    // Read SQL file
    $sql = file_get_contents($sql_file);
    if ($sql === false) {
        echo "Error: Could not read SQL file: $sql_file\n";
        return false;
    }

    // Connect to database
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        echo "Error: Database connection failed: " . $conn->connect_error . "\n";
        return false;
    }

    // Split SQL into individual statements
    $statements = explode(';', $sql);

    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                echo "Error executing SQL: " . $conn->error . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
                $conn->close();
                return false;
            }
        }
    }

    $conn->close();
    return true;
}

// Command line interface
if (PHP_SAPI === 'cli' && basename($_SERVER['PHP_SELF']) === 'process_sql.php') {
    // Check if file argument is provided
    if ($argc < 2) {
        echo "Usage: php process_sql.php <sql_file> [output_file] [--execute]\n";
        echo "Options:\n";
        echo "  --execute    Execute the processed SQL file\n";
        exit(1);
    }

    $input_file = $argv[1];
    $output_file = isset($argv[2]) && $argv[2] !== '--execute' ? $argv[2] : null;
    $execute = in_array('--execute', $argv);

    // Process the SQL file
    $result = process_sql_file($input_file, $output_file, $db_name_default, $prefix_default, $create_db_default);
    if ($result) {
        echo "SQL file processed successfully. Output: $result\n";

        // Execute the SQL file if requested
        if ($execute) {
            echo "Executing SQL file...\n";
            $db_host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
            $db_user = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '';
            $db_pass = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '';

            if (execute_sql_file($result, $db_host, $db_user, $db_pass)) {
                echo "SQL file executed successfully.\n";
                exit(0);
            } else {
                echo "Failed to execute SQL file.\n";
                exit(1);
            }
        }

        exit(0);
    } else {
        echo "Failed to process SQL file.\n";
        exit(1);
    }
}
