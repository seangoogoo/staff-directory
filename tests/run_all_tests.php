<?php
/**
 * Run All Tests
 *
 * This script runs all the test scripts for the database configuration and installer.
 */

echo "Running All Tests\n";
echo "================\n\n";

// Define the test scripts to run
$tests = [
    'test_default_config.php',
    'test_custom_db_name.php',
    'test_table_prefix.php',
    'test_web_installer.php'
];

// Skip the migration script test if runkit extension is not available
if (extension_loaded('runkit') || extension_loaded('runkit7')) {
    $tests[] = 'test_migration_script.php';
} else {
    echo "Warning: Skipping migration script test because runkit extension is not available.\n\n";
}

// Run each test
$success_count = 0;
$failure_count = 0;

foreach ($tests as $test) {
    echo "Running test: $test\n";
    echo str_repeat('-', 80) . "\n";
    
    // Execute the test script
    $output = [];
    $return_var = 0;
    exec('php ' . __DIR__ . '/' . $test . ' 2>&1', $output, $return_var);
    
    // Display the output
    foreach ($output as $line) {
        echo $line . "\n";
    }
    
    // Check the result
    if ($return_var === 0) {
        echo "\nTest passed!\n\n";
        $success_count++;
    } else {
        echo "\nTest failed with return code: $return_var\n\n";
        $failure_count++;
    }
    
    echo str_repeat('=', 80) . "\n\n";
}

// Display summary
echo "Test Summary\n";
echo "===========\n";
echo "Total tests: " . count($tests) . "\n";
echo "Passed: $success_count\n";
echo "Failed: $failure_count\n\n";

if ($failure_count === 0) {
    echo "All tests passed!\n";
    exit(0);
} else {
    echo "Some tests failed.\n";
    exit(1);
}
