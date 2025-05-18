<?php
/**
 * Test Migration Script
 *
 * This script tests the database migration script:
 * 1. Sets up a database with default tables (no prefix)
 * 2. Updates the configuration to use a table prefix
 * 3. Runs the migration script to rename the tables
 * 4. Verifies that the tables were renamed correctly
 */

echo "Testing Migration Script\n";
echo "======================\n\n";

// Backup current .env file
$env_file = __DIR__ . '/../staff_dir_env/.env';
$env_backup = $env_file . '.backup';
copy($env_file, $env_backup);

// Load environment variables
putenv("DB_NAME=staff_dir_migration");
putenv("DB_TABLE_PREFIX=");
putenv("DB_CREATE_DATABASE=true");
putenv("DB_INSTALLED=false");
require_once __DIR__ . '/../config/env_loader.php';

echo "1. Setting up test environment...\n";

// Update .env file with initial settings (no prefix)
$env_content = file_get_contents($env_file);
$env_content = preg_replace('/DB_NAME=.*/', 'DB_NAME=staff_dir_migration', $env_content);
$env_content = preg_replace('/DB_TABLE_PREFIX=.*/', 'DB_TABLE_PREFIX=', $env_content);
$env_content = preg_replace('/DB_CREATE_DATABASE=.*/', 'DB_CREATE_DATABASE=true', $env_content);
$env_content = preg_replace('/DB_INSTALLED=.*/', 'DB_INSTALLED=false', $env_content);
file_put_contents($env_file, $env_content);

echo "   Environment configured with:\n";
echo "   - Database: staff_dir_migration\n";
echo "   - Table Prefix: (none)\n";
echo "   - Create Database: true\n\n";

// Drop the test database if it exists
echo "2. Preparing test database...\n";
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
$conn->query("DROP DATABASE IF EXISTS staff_dir_migration");
echo "   Test database dropped (if existed)\n\n";

// Load database configuration
echo "3. Loading database configuration...\n";
require_once __DIR__ . '/../config/database.php';

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
echo "5. Initializing database with default tables (no prefix)...\n";
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
echo "6. Verifying initial tables...\n";
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
$expected_tables = ['companies', 'departments', 'staff_members', 'app_settings'];
$missing_tables = array_diff($expected_tables, $tables);

if (empty($missing_tables)) {
    echo "   All expected tables found!\n\n";
} else {
    echo "   Missing tables: " . implode(', ', $missing_tables) . "\n\n";
    exit(1);
}

// Update .env file to use a table prefix
echo "7. Updating environment to use table prefix...\n";
$env_content = file_get_contents($env_file);
$env_content = preg_replace('/DB_TABLE_PREFIX=.*/', 'DB_TABLE_PREFIX=sd_', $env_content);
file_put_contents($env_file, $env_content);
echo "   Environment updated with table prefix: sd_\n\n";

// Run the migration script
echo "8. Running migration script...\n";
$output = [];
$return_var = 0;
exec('php ' . __DIR__ . '/../database/migrate_tables.php --force 2>&1', $output, $return_var);

echo "   Migration script output:\n";
foreach ($output as $line) {
    echo "   " . $line . "\n";
}
echo "\n";

if ($return_var !== 0) {
    echo "   Migration script failed with return code: " . $return_var . "\n\n";
    exit(1);
}

// Reload database configuration to get updated constants
echo "9. Reloading database configuration...\n";
// We can't easily redefine constants, so we'll just check the tables directly
putenv("DB_TABLE_PREFIX=sd_");
require_once __DIR__ . '/../config/env_loader.php';

// Check if constants are defined correctly
echo "   Checking constants:\n";
echo "   - DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "\n";
echo "   - DB_TABLE_PREFIX: '" . (defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : 'Not defined') . "'\n";
echo "   - TABLE_COMPANIES: " . (defined('TABLE_COMPANIES') ? TABLE_COMPANIES : 'Not defined') . "\n";
echo "   - TABLE_DEPARTMENTS: " . (defined('TABLE_DEPARTMENTS') ? TABLE_DEPARTMENTS : 'Not defined') . "\n";
echo "   - TABLE_STAFF_MEMBERS: " . (defined('TABLE_STAFF_MEMBERS') ? TABLE_STAFF_MEMBERS : 'Not defined') . "\n";
echo "   - TABLE_APP_SETTINGS: " . (defined('TABLE_APP_SETTINGS') ? TABLE_APP_SETTINGS : 'Not defined') . "\n\n";

// Check if tables were renamed
echo "10. Verifying renamed tables...\n";
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
echo "11. Testing a simple query...\n";
$result = $conn->query("SELECT * FROM " . TABLE_COMPANIES);
if ($result) {
    echo "   Query successful! Found " . $result->num_rows . " companies.\n\n";
} else {
    echo "   Query failed: " . $conn->error . "\n\n";
    exit(1);
}

// Restore the original .env file
echo "12. Cleaning up...\n";
copy($env_backup, $env_file);
unlink($env_backup);
echo "   Original .env file restored\n\n";

echo "Test completed successfully!\n";
$conn->close();
