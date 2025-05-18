<?php
/**
 * Test Table Prefix
 *
 * This script tests the database configuration with table prefixes:
 * - Default database name (staff_dir)
 * - Table prefix (sd_)
 * - Create database if it doesn't exist
 */

echo "Testing Table Prefix\n";
echo "===================\n\n";

// Backup current .env file
$env_file = __DIR__ . '/../staff_dir_env/.env';
$env_backup = $env_file . '.backup';
copy($env_file, $env_backup);

// Load environment variables
putenv("DB_NAME=staff_dir");
putenv("DB_TABLE_PREFIX=sd_");
putenv("DB_CREATE_DATABASE=true");
putenv("DB_INSTALLED=false");
require_once __DIR__ . '/../config/env_loader.php';

echo "1. Setting up test environment...\n";

// Update .env file with test settings
$env_content = file_get_contents($env_file);
$env_content = preg_replace('/DB_NAME=.*/', 'DB_NAME=staff_dir', $env_content);
$env_content = preg_replace('/DB_TABLE_PREFIX=.*/', 'DB_TABLE_PREFIX=sd_', $env_content);
$env_content = preg_replace('/DB_CREATE_DATABASE=.*/', 'DB_CREATE_DATABASE=true', $env_content);
$env_content = preg_replace('/DB_INSTALLED=.*/', 'DB_INSTALLED=false', $env_content);
file_put_contents($env_file, $env_content);

echo "   Environment configured with:\n";
echo "   - Database: staff_dir\n";
echo "   - Table Prefix: sd_\n";
echo "   - Create Database: true\n\n";

// Drop the test database if it exists
echo "2. Preparing test database...\n";
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
$conn->query("DROP DATABASE IF EXISTS staff_dir");
echo "   Test database dropped (if existed)\n\n";

// Load database configuration
echo "3. Loading database configuration...\n";
require_once __DIR__ . '/../config/database.php';

// Check if constants are defined correctly
echo "   Checking constants:\n";
echo "   - DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "\n";
echo "   - DB_TABLE_PREFIX: '" . (defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'Not defined') . "'\n";
echo "   - TABLE_COMPANIES: " . (defined('TABLE_COMPANIES') ? TABLE_COMPANIES : 'Not defined') . "\n";
echo "   - TABLE_DEPARTMENTS: " . (defined('TABLE_DEPARTMENTS') ? TABLE_DEPARTMENTS : 'Not defined') . "\n";
echo "   - TABLE_STAFF_MEMBERS: " . (defined('TABLE_STAFF_MEMBERS') ? TABLE_STAFF_MEMBERS : 'Not defined') . "\n";
echo "   - TABLE_APP_SETTINGS: " . (defined('TABLE_APP_SETTINGS') ? TABLE_APP_SETTINGS : 'Not defined') . "\n\n";

// Check database connection
echo "4. Testing database connection...\n";
try {
    $conn->query("SELECT 1");
    echo "   Connection successful!\n\n";
} catch (Exception $e) {
    echo "   Connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Process and execute SQL file
echo "5. Initializing database...\n";
require_once __DIR__ . '/../database/process_sql.php';
$temp_sql = tempnam(sys_get_temp_dir(), 'sql_');
$result = process_sql_file(__DIR__ . '/../database/staff_dir_clean.sql', $temp_sql, DB_NAME, DB_TABLE_PREFIX, true);

if (!$result) {
    echo "   Error processing SQL file\n\n";
    exit(1);
}

// Execute the processed SQL file
$conn->select_db(DB_NAME);
$sql = file_get_contents($temp_sql);
$statements = explode(';', $sql);
$error = false;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!$conn->query($statement)) {
            echo "   Error executing SQL: " . $conn->error . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            $error = true;
            break;
        }
    }
}

if (!$error) {
    echo "   Database initialized successfully!\n\n";
} else {
    echo "   Database initialization failed!\n\n";
    exit(1);
}

// Clean up
unlink($temp_sql);

// Check if tables were created
echo "6. Verifying tables...\n";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo "   Tables in database:\n";
foreach ($tables as $table) {
    echo "   - " . $table . "\n";
}
echo "\n";

// Check if tables match the expected names
$expected_tables = ['sd_companies', 'sd_departments', 'sd_staff_members', 'sd_app_settings'];
$missing_tables = array_diff($expected_tables, $tables);

if (empty($missing_tables)) {
    echo "   All expected tables found!\n\n";
} else {
    echo "   Missing tables: " . implode(', ', $missing_tables) . "\n\n";
    exit(1);
}

// Test a simple query
echo "7. Testing a simple query...\n";
$result = $conn->query("SELECT * FROM " . TABLE_COMPANIES);
if ($result) {
    echo "   Query successful! Found " . $result->num_rows . " companies.\n\n";
} else {
    echo "   Query failed: " . $conn->error . "\n\n";
    exit(1);
}

// Restore the original .env file
echo "8. Cleaning up...\n";
copy($env_backup, $env_file);
unlink($env_backup);
echo "   Original .env file restored\n\n";

echo "Test completed successfully!\n";
$conn->close();
