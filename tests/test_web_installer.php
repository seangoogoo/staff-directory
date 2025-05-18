<?php
/**
 * Test Web Installer
 *
 * This script tests the web installer by simulating HTTP requests:
 * 1. Tests the connection test functionality
 * 2. Tests the installation process
 */

echo "Testing Web Installer\n";
echo "====================\n\n";

// Backup current .env file
$env_file = __DIR__ . '/../staff_dir_env/.env';
$env_backup = $env_file . '.backup';
copy($env_file, $env_backup);

// Load environment variables
putenv("DB_NAME=staff_dir_installer");
putenv("DB_TABLE_PREFIX=");
putenv("DB_CREATE_DATABASE=true");
putenv("DB_INSTALLED=false");
require_once __DIR__ . '/../config/env_loader.php';

echo "1. Setting up test environment...\n";

// Update .env file to reset installation status
$env_content = file_get_contents($env_file);
$env_content = preg_replace('/DB_NAME=.*/', 'DB_NAME=staff_dir_installer', $env_content);
$env_content = preg_replace('/DB_TABLE_PREFIX=.*/', 'DB_TABLE_PREFIX=', $env_content);
$env_content = preg_replace('/DB_CREATE_DATABASE=.*/', 'DB_CREATE_DATABASE=true', $env_content);
$env_content = preg_replace('/DB_INSTALLED=.*/', 'DB_INSTALLED=false', $env_content);
file_put_contents($env_file, $env_content);

echo "   Environment configured with:\n";
echo "   - Database: staff_dir_installer\n";
echo "   - Table Prefix: (none)\n";
echo "   - Create Database: true\n";
echo "   - Installed: false\n\n";

// Drop the test database if it exists
echo "2. Preparing test database...\n";
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
$conn->query("DROP DATABASE IF EXISTS staff_dir_installer");
echo "   Test database dropped (if existed)\n\n";

// Include the installer file to access its functions
echo "3. Loading installer functions...\n";
require_once __DIR__ . '/../public/install.php';
echo "   Installer functions loaded\n\n";

// Test database connection function
echo "4. Testing connection test functionality...\n";
$result = test_db_connection($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
if ($result['success']) {
    echo "   Connection test successful: " . $result['message'] . "\n\n";
} else {
    echo "   Connection test failed: " . $result['message'] . "\n\n";
    exit(1);
}

// Test database initialization function
echo "5. Testing database initialization...\n";
$result = initialize_database(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    'staff_dir_installer',
    'sd_',
    true
);

if ($result['success']) {
    echo "   Database initialization successful: " . $result['message'] . "\n\n";
} else {
    echo "   Database initialization failed: " . $result['message'] . "\n\n";
    exit(1);
}

// Check if tables were created
echo "6. Verifying tables...\n";
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], 'staff_dir_installer');
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

// Test updating the .env file
echo "7. Testing .env file update...\n";
$form_data = [
    'DB_HOST' => $_ENV['DB_HOST'],
    'DB_USER' => $_ENV['DB_USER'],
    'DB_PASS' => $_ENV['DB_PASS'],
    'DB_NAME' => 'staff_dir_installer',
    'DB_TABLE_PREFIX' => 'sd_',
    'DB_CREATE_DATABASE' => 'true',
    'DB_INSTALLED' => 'true',
    'ADMIN_USERNAME' => 'admin',
    'ADMIN_PASSWORD' => 'admin'
];

$result = update_env_file($form_data);
if ($result) {
    echo "   .env file updated successfully\n\n";
} else {
    echo "   Failed to update .env file\n\n";
    exit(1);
}

// Check if the installation status was updated
echo "8. Verifying installation status...\n";
$installed = is_installed();
if ($installed) {
    echo "   Installation status verified: Installed\n\n";
} else {
    echo "   Installation status verification failed: Not installed\n\n";
    exit(1);
}

// Restore the original .env file
echo "9. Cleaning up...\n";
copy($env_backup, $env_file);
unlink($env_backup);
echo "   Original .env file restored\n\n";

echo "Test completed successfully!\n";
$conn->close();
