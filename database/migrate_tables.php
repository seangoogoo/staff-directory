<?php
/**
 * Database Migration Script for Table Prefixing
 *
 * This script renames existing tables to add the configured prefix.
 * It should be run when:
 * 1. An existing database needs to be updated to use table prefixes
 * 2. The table prefix configuration has changed
 *
 * Usage:
 * php migrate_tables.php [--dry-run] [--force]
 *
 * Options:
 * --dry-run  Show what would be done without making changes
 * --force    Skip confirmation prompt
 */

// Load environment variables and database configuration
require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/database.php';

// Define the tables that need to be migrated
$tables = [
    'companies',
    'departments',
    'staff_members',
    'app_settings'
];

// Get the table prefix from environment
$prefix = isset($_ENV['DB_TABLE_PREFIX']) ? $_ENV['DB_TABLE_PREFIX'] : '';

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);

// Function to check if a table exists
function table_exists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Function to get all tables in the database
function get_all_tables($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Function to rename a table
function rename_table($conn, $old_name, $new_name, $dry_run = false) {
    if ($dry_run) {
        echo "Would rename table '$old_name' to '$new_name'\n";
        return true;
    }

    $sql = "RENAME TABLE `$old_name` TO `$new_name`";
    if ($conn->query($sql)) {
        echo "Renamed table '$old_name' to '$new_name'\n";
        return true;
    } else {
        echo "Error renaming table '$old_name': " . $conn->error . "\n";
        return false;
    }
}

// Main migration function
function migrate_tables($conn, $tables, $prefix, $dry_run = false) {
    // Get all existing tables
    $existing_tables = get_all_tables($conn);
    
    // Track migration status
    $success_count = 0;
    $error_count = 0;
    $skipped_count = 0;
    
    echo "\nStarting table migration " . ($dry_run ? "(DRY RUN)" : "") . ":\n";
    echo "----------------------------------------\n";
    
    // Process each table
    foreach ($tables as $table) {
        // Skip if the table doesn't exist
        if (!in_array($table, $existing_tables)) {
            echo "Table '$table' does not exist - skipping\n";
            $skipped_count++;
            continue;
        }
        
        // Skip if the prefixed table already exists
        $prefixed_table = $prefix . $table;
        if (in_array($prefixed_table, $existing_tables)) {
            echo "Prefixed table '$prefixed_table' already exists - skipping\n";
            $skipped_count++;
            continue;
        }
        
        // Skip if the table already has the prefix
        if (strpos($table, $prefix) === 0) {
            echo "Table '$table' already has the prefix - skipping\n";
            $skipped_count++;
            continue;
        }
        
        // Rename the table
        if (rename_table($conn, $table, $prefixed_table, $dry_run)) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    echo "----------------------------------------\n";
    echo "Migration summary:\n";
    echo "  Success: $success_count\n";
    echo "  Errors: $error_count\n";
    echo "  Skipped: $skipped_count\n";
    
    return $error_count === 0;
}

// Main execution
echo "Database Table Migration Tool\n";
echo "============================\n";

// Check if prefix is empty
if (empty($prefix)) {
    echo "No table prefix configured. Nothing to do.\n";
    exit(0);
}

// Show configuration
echo "Database: " . DB_NAME . "\n";
echo "Table Prefix: '" . $prefix . "'\n";
echo "Tables to migrate: " . implode(", ", $tables) . "\n";

// Confirm before proceeding
if (!$dry_run && !$force) {
    echo "\nWARNING: This will rename tables in your database.\n";
    echo "Make sure you have a backup before proceeding.\n";
    echo "Continue? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    if (strtolower($line) !== 'y') {
        echo "Migration cancelled.\n";
        exit(0);
    }
}

// Perform the migration
$result = migrate_tables($conn, $tables, $prefix, $dry_run);

// Close the database connection
$conn->close();

// Exit with appropriate status code
exit($result ? 0 : 1);
