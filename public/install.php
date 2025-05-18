<?php
/**
 * Staff Directory Installer
 *
 * This script provides a web-based installer for the Staff Directory application.
 * It allows users to:
 * 1. Test database connection
 * 2. Configure database settings
 * 3. Initialize the database with required tables
 * 4. Create admin user
 */

// Define constants
define('INSTALLER_VERSION', '1.0.2');
define('APP_NAME', 'Staff Directory');
define('ENV_FILE_PATH', __DIR__ . '/../staff_dir_env/.env');
define('ENV_EXAMPLE_PATH', __DIR__ . '/../staff_dir_env/.env_example');
define('SQL_CLEAN_PATH', __DIR__ . '/../database/staff_dir_clean.sql');
define('SQL_EXAMPLE_PATH', __DIR__ . '/../database/staff_dir.sql');
define('SQL_PROCESSOR_PATH', __DIR__ . '/../database/process_sql.php');

// Define path constants required by LanguageManager
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__)); // Project root
    define('PRIVATE_PATH', BASE_PATH); // Private files directory
    define('PUBLIC_PATH', __DIR__); // Path to the public directory
    define('APP_BASE_URI', ''); // The base URI for routing
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the language manager
require_once __DIR__ . '/includes/LanguageManager.php';

// Check if the application is already installed
function is_installed() {
    // If .env file exists and DB_INSTALLED=true, consider it installed
    if (file_exists(ENV_FILE_PATH)) {
        $env_content = file_get_contents(ENV_FILE_PATH);
        if (strpos($env_content, 'DB_INSTALLED=true') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Validates form data for database connection and installation
 *
 * @param array $form_data Form data to validate
 * @param string $action Current action (test_connection or install)
 * @return array Validation result with status and messages
 */
function validate_form_data($form_data, $action) {
    $errors = [];
    $required_fields = [];

    // Define required fields based on action
    if ($action === 'test_connection') {
        $required_fields = [
            'DB_HOST' => __('database_host'),
            'DB_USER' => __('database_user'),
            'DB_PASS' => __('database_password')
        ];
    } else if ($action === 'install') {
        $required_fields = [
            'DB_HOST' => __('database_host'),
            'DB_USER' => __('database_user'),
            'DB_PASS' => __('database_password'),
            'DB_NAME' => __('database_name'),
            'ADMIN_USERNAME' => __('admin_username'),
            'ADMIN_PASSWORD' => __('admin_password')
        ];
    }

    // Check for empty required fields
    foreach ($required_fields as $field => $label) {
        if (empty($form_data[$field])) {
            $errors[$field] = sprintf(__('field_required'), $label);
        }
    }

    // Additional validation rules
    if (empty($errors['DB_NAME']) && $action === 'install') {
        // Validate database name (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['DB_NAME'])) {
            $errors['DB_NAME'] = __('database_name_invalid_chars');
        }
    }

    // Validate table prefix if provided
    if (!empty($form_data['DB_TABLE_PREFIX'])) {
        // Remove trailing underscore for validation
        $prefix = rtrim($form_data['DB_TABLE_PREFIX'], '_');

        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            $errors['DB_TABLE_PREFIX'] = __('prefix_invalid_chars');
        }
    }

    // Validate admin password strength if installing
    if (empty($errors['ADMIN_PASSWORD']) && $action === 'install') {
        if (strlen($form_data['ADMIN_PASSWORD']) < 6) {
            $errors['ADMIN_PASSWORD'] = __('password_too_short');
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// Test database connection
function test_db_connection($host, $user, $pass, $name = '', $create_db = false) {
    // Connect to the database server (without selecting a database)
    $conn = @new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        return [
            'success' => false,
            'message' => sprintf(__('connection_failed'), $conn->connect_error)
        ];
    }

    // If a database name is provided and we want to create it
    if (!empty($name) && $create_db) {
        $sql = "CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($name) . "`";
        if (!$conn->query($sql)) {
            return [
                'success' => false,
                'message' => sprintf(__('database_creation_failed'), $conn->error)
            ];
        }
    }

    // If a database name is provided, try to select it
    if (!empty($name)) {
        if (!$conn->select_db($name)) {
            return [
                'success' => false,
                'message' => sprintf(__('connection_failed'), "Could not select database: " . $conn->error)
            ];
        }
    }

    $conn->close();
    return [
        'success' => true,
        'message' => __('connection_successful')
    ];
}

// Create or update .env file
function update_env_file($data) {
    // If .env file doesn't exist, copy from .env_example
    if (!file_exists(ENV_FILE_PATH) && file_exists(ENV_EXAMPLE_PATH)) {
        copy(ENV_EXAMPLE_PATH, ENV_FILE_PATH);
    }

    // If still doesn't exist, create it
    if (!file_exists(ENV_FILE_PATH)) {
        file_put_contents(ENV_FILE_PATH, "# Staff Directory Environment Configuration\n");
    }

    // Read current content
    $content = file_get_contents(ENV_FILE_PATH);

    // Update each setting
    foreach ($data as $key => $value) {
        // Escape any quotes in the value
        $value = str_replace('"', '\"', $value);

        // Check if the key already exists
        if (preg_match('/^' . $key . '=.*$/m', $content)) {
            // Update existing key
            $content = preg_replace('/^' . $key . '=.*$/m', $key . '=' . $value, $content);
        } else {
            // Add new key
            $content .= "\n" . $key . '=' . $value;
        }
    }

    // Write back to file
    return file_put_contents(ENV_FILE_PATH, $content) !== false;
}

// Initialize database
function initialize_database($host, $user, $pass, $name, $prefix, $create_db, $use_example_data = false) {
    // First, test the connection to the server
    $test = test_db_connection($host, $user, $pass);
    if (!$test['success']) {
        return $test;
    }

    // Create database if needed and test connection with database name
    $test = test_db_connection($host, $user, $pass, $name, $create_db);
    if (!$test['success']) {
        return $test;
    }

    // Determine which SQL file to use
    $sql_file = $use_example_data ? SQL_EXAMPLE_PATH : SQL_CLEAN_PATH;

    // Process the SQL file
    if (!file_exists($sql_file)) {
        return [
            'success' => false,
            'message' => "SQL file not found: " . $sql_file
        ];
    }

    if (!file_exists(SQL_PROCESSOR_PATH)) {
        return [
            'success' => false,
            'message' => "SQL processor not found: " . SQL_PROCESSOR_PATH
        ];
    }

    // Create a temporary processed SQL file
    $temp_sql = tempnam(sys_get_temp_dir(), 'sql_');

    // Set environment variables for the processor
    putenv("DB_NAME=$name");
    putenv("DB_TABLE_PREFIX=$prefix");
    putenv("DB_CREATE_DATABASE=" . ($create_db ? 'true' : 'false'));

    // Debug: Log the environment variables being set
    error_log("Setting environment variables for SQL processor: DB_NAME=$name, DB_TABLE_PREFIX=$prefix, DB_CREATE_DATABASE=" . ($create_db ? 'true' : 'false'));

    // Verify the environment variables were set correctly
    error_log("Verifying environment variables: DB_NAME=" . getenv('DB_NAME') . ", DB_TABLE_PREFIX=" . getenv('DB_TABLE_PREFIX') . ", DB_CREATE_DATABASE=" . getenv('DB_CREATE_DATABASE'));

    // Include the processor and process the SQL file
    require_once SQL_PROCESSOR_PATH;

    // Ensure the prefix is properly set and formatted
    if (empty($prefix)) {
        $prefix = '';
    } else {
        // Make sure the prefix ends with an underscore
        if (substr($prefix, -1) !== '_') {
            $prefix .= '_';
            error_log("Added underscore to prefix in initialize_database: $prefix");
        }
    }

    // Debug: Log the values being used
    error_log("Processing SQL file with: DB_NAME=$name, PREFIX=$prefix, CREATE_DB=" . ($create_db ? 'true' : 'false'));

    $result = process_sql_file($sql_file, $temp_sql, $name, $prefix, $create_db);

    if (!$result) {
        return [
            'success' => false,
            'message' => "Error processing SQL file"
        ];
    }

    // Execute the processed SQL file
    $conn = new mysqli($host, $user, $pass);

    // Select the database
    if (!$conn->select_db($name)) {
        return [
            'success' => false,
            'message' => "Error selecting database: " . $conn->error
        ];
    }

    // Read and execute the SQL file
    $sql = file_get_contents($temp_sql);

    // Debug: Check if the prefix was properly applied in the SQL
    // Ensure prefix is properly formatted for comparison
    $check_prefix = $prefix;
    if ($check_prefix !== '' && substr($check_prefix, -1) !== '_') {
        $check_prefix .= '_';
    }

    $sample_table = "CREATE TABLE IF NOT EXISTS `" . $check_prefix . "companies`";
    if (strpos($sql, $sample_table) === false) {
        error_log("WARNING: Prefix not found in processed SQL. Expected: $sample_table");
        error_log("First 500 chars of SQL: " . substr($sql, 0, 500));

        // Check for empty prefix (no prefix)
        $no_prefix_sample = "CREATE TABLE IF NOT EXISTS `companies`";
        if (strpos($sql, $no_prefix_sample) !== false) {
            error_log("Found tables without prefix: $no_prefix_sample");
        }

        // Try without the underscore
        if ($check_prefix !== '' && substr($check_prefix, -1) === '_') {
            $alt_prefix = substr($check_prefix, 0, -1);
            $alt_sample = "CREATE TABLE IF NOT EXISTS `" . $alt_prefix . "companies`";
            if (strpos($sql, $alt_sample) !== false) {
                error_log("Found alternative prefix format: $alt_sample");
            }
        }

        // This is a critical error - the prefix from the form is not being applied
        error_log("CRITICAL ERROR: The table prefix '$prefix' from the form is not being applied to the SQL!");
        error_log("Form data: " . print_r($form_data ?? [], true));
        error_log("Environment variables: DB_TABLE_PREFIX=" . getenv('DB_TABLE_PREFIX') . ", DB_NAME=" . getenv('DB_NAME'));
    } else {
        error_log("Prefix found in processed SQL: $sample_table");
    }

    // Split SQL into individual statements
    $statements = explode(';', $sql);

    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                return [
                    'success' => false,
                    'message' => "Error executing SQL: " . $conn->error . "\nStatement: " . substr($statement, 0, 100) . "..."
                ];
            }
        }
    }

    // Clean up
    unlink($temp_sql);
    $conn->close();

    return [
        'success' => true,
        'message' => "Database initialized successfully!"
    ];
}

// Handle form submission
$message = '';
$message_type = '';
$form_data = [];
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $db_prefix = isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : 'sd_';

    // Ensure the prefix is not empty and properly formatted
    if ($db_prefix !== '' && substr($db_prefix, -1) !== '_') {
        $db_prefix .= '_';
    }

    $form_data = [
        'DB_HOST' => $_POST['db_host'] ?? '',
        'DB_USER' => $_POST['db_user'] ?? '',
        'DB_PASS' => $_POST['db_pass'] ?? '',
        'DB_NAME' => $_POST['db_name'] ?? '',
        'DB_TABLE_PREFIX' => $db_prefix,
        'DB_CREATE_DATABASE' => isset($_POST['db_create']) ? 'true' : 'false',
        'ADMIN_USERNAME' => $_POST['admin_user'] ?? '',
        'ADMIN_PASSWORD' => $_POST['admin_pass'] ?? '',
        'USE_EXAMPLE_DATA' => isset($_POST['use_example_data']) ? 'true' : 'false'
    ];

    // Debug: Log the form data
    error_log("Form data: DB_NAME={$form_data['DB_NAME']}, DB_TABLE_PREFIX={$form_data['DB_TABLE_PREFIX']}");

    // Check action
    $action = $_POST['action'] ?? '';

    // Validate form data
    $validation = validate_form_data($form_data, $action);

    if (!$validation['valid']) {
        // Form has validation errors
        $message = __('please_fix_errors');
        $message_type = 'error';
        $form_errors = $validation['errors'];
    } else {
        // Form is valid, proceed with action
        if ($action === 'test_connection') {
            // Test database connection
            $result = test_db_connection(
                $form_data['DB_HOST'],
                $form_data['DB_USER'],
                $form_data['DB_PASS']
            );

            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        else if ($action === 'install') {
            // Initialize database
            $result = initialize_database(
                $form_data['DB_HOST'],
                $form_data['DB_USER'],
                $form_data['DB_PASS'],
                $form_data['DB_NAME'],
                $form_data['DB_TABLE_PREFIX'],
                $form_data['DB_CREATE_DATABASE'] === 'true',
                $form_data['USE_EXAMPLE_DATA'] === 'true'
            );

            if ($result['success']) {
                // Update .env file with all settings
                $form_data['DB_INSTALLED'] = 'true';
                $update_result = update_env_file($form_data);

                if ($update_result) {
                    $message = __('installation_completed');
                    $message_type = 'success';
                } else {
                    $message = __('database_initialized_env_failed');
                    $message_type = 'error';
                }
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        }
    }
}

// Check if already installed
$installed = is_installed();

// Load default values from .env_example if available
if (file_exists(ENV_EXAMPLE_PATH) && empty($form_data)) {
    $env_example = file_get_contents(ENV_EXAMPLE_PATH);
    preg_match('/DB_HOST=(.*)/', $env_example, $host_matches);
    preg_match('/DB_NAME=(.*)/', $env_example, $name_matches);

    $form_data['DB_HOST'] = $host_matches[1] ?? 'localhost';
    $form_data['DB_NAME'] = $name_matches[1] ?? 'staff_dir';

    // Ensure the prefix is properly formatted
    $db_prefix = 'sd_';
    if (substr($db_prefix, -1) !== '_') {
        $db_prefix .= '_';
    }
    $form_data['DB_TABLE_PREFIX'] = $db_prefix;

    $form_data['DB_CREATE_DATABASE'] = 'true';
    $form_data['USE_EXAMPLE_DATA'] = 'false';
    $form_data['ADMIN_USERNAME'] = 'admin';
}

// HTML output starts here
?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> <?php echo __('installer'); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
</head>
<body class="bg-gray-50 min-h-screen antialiased font-sans text-gray-900 flex flex-col">
    <!-- Admin Header: White background, padding, border, flex layout -->
    <header class="admin-header bg-white shadow-sm py-3 px-4 border-b border-gray-200">
        <!-- Container: Standard width, centered, flex for layout -->
        <div class="container w-full max-w-screen-xl mx-auto flex items-center justify-between">
            <!-- Site Branding: Flex, logo height, title -->
            <div class="site-branding flex items-center gap-3">
                <?php
                // Get logo path - using relative path since url() function isn't available in installer
                $logo_path = 'assets/images/staff-directory-logo.svg';
                ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo APP_NAME; ?> Logo" class="h-10 w-auto">
                <span class="text-2xl font-light text-gray-800"><?php echo APP_NAME; ?> <?php echo __('installer'); ?></span>
            </div>

            <div>
                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-md border border-gray-200"><?php echo __('installer_version'); ?><?php echo INSTALLER_VERSION; ?></span>
            </div>
        </div>
    </header>

    <!-- Main Container: Padding + flex-grow -->
    <main class="container w-full max-w-screen-xl mx-auto px-4 py-6 flex-grow bg-gray-50">
        <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-md p-6 border border-gray-200">
            <div class="border-b border-gray-200 pb-4 mb-6">
                <h2 class="text-xl font-medium text-gray-800 flex items-center">
                    <i class="ri-settings-4-line mr-2 text-gray-600"></i>
                    <?php echo __('installation_setup'); ?>
                </h2>
                <p class="text-gray-500 mt-1 text-sm"><?php echo __('configure_database_admin'); ?></p>
            </div>

        <?php if ($installed): ?>
            <div class="bg-green-50 border-l-4 border-green-500 px-4 mb-4 rounded-md flex items-center">
                <i class="ri-check-line text-2xl mr-3 mt-1 text-green-600"></i>
                <div>
                    <p class="font-semibold text-lg text-green-800"><?php echo __('is_installed'); ?></p>
                    <p class="mt-2 text-green-700"><?php echo __('reinstall_instructions'); ?></p>
                </div>
            </div>
            <p class="mt-4">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <i class="ri-arrow-right-line mr-1"></i>
                    <?php echo __('go_to_application'); ?>
                </a>
            </p>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'bg-red-50 border-l-4 border-red-500 text-red-700'; ?> px-4 mb-4 rounded-md flex items-center">
                    <i class="ri-<?php echo $message_type === 'success' ? 'check-line text-green-600' : 'error-warning-line text-red-600'; ?> text-2xl mr-3 mt-1"></i>
                    <div>
                        <?php echo $message; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="install.php" class="space-y-6">
                <div class="mb-6">
                    <h2 class="text-lg font-medium mb-4 text-gray-800 flex items-center">
                        <i class="ri-database-2-line mr-2 text-gray-600"></i>
                        <?php echo __('database_configuration'); ?>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="db_host" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('database_host'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-server-line text-gray-400"></i>
                                </div>
                                <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($form_data['DB_HOST'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['DB_HOST']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['DB_HOST'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['DB_HOST']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="db_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('database_name'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-database-line text-gray-400"></i>
                                </div>
                                <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($form_data['DB_NAME'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['DB_NAME']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['DB_NAME'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['DB_NAME']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="db_user" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('database_user'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-user-line text-gray-400"></i>
                                </div>
                                <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($form_data['DB_USER'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['DB_USER']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['DB_USER'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['DB_USER']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('database_password'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-lock-line text-gray-400"></i>
                                </div>
                                <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($form_data['DB_PASS'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['DB_PASS']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['DB_PASS'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['DB_PASS']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="db_prefix" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('table_prefix'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-table-line text-gray-400"></i>
                                </div>
                                <input type="text" id="db_prefix" name="db_prefix" value="<?php echo htmlspecialchars($form_data['DB_TABLE_PREFIX'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['DB_TABLE_PREFIX']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['DB_TABLE_PREFIX'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['DB_TABLE_PREFIX']; ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1 flex items-center">
                                <i class="ri-information-line mr-1 text-indigo-500"></i>
                                <?php echo __('prefix_note'); ?>
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="db_create" name="db_create" <?php echo (($form_data['DB_CREATE_DATABASE'] ?? 'true') === 'true') ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="db_create" class="ml-2 block text-sm text-gray-700"><?php echo __('create_database'); ?></label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="use_example_data" name="use_example_data" <?php echo (($form_data['USE_EXAMPLE_DATA'] ?? 'false') === 'true') ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="use_example_data" class="ml-2 block text-sm text-gray-700"><?php echo __('include_example_data'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" name="action" value="test_connection" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="ri-link-check-line mr-1"></i>
                            <?php echo __('test_connection'); ?>
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-lg font-medium mb-4 text-gray-800 flex items-center">
                        <i class="ri-admin-line mr-2 text-gray-600"></i>
                        <?php echo __('admin_account'); ?>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="admin_user" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('admin_username'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-user-3-line text-gray-400"></i>
                                </div>
                                <input type="text" id="admin_user" name="admin_user" value="<?php echo htmlspecialchars($form_data['ADMIN_USERNAME'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['ADMIN_USERNAME']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['ADMIN_USERNAME'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['ADMIN_USERNAME']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="admin_pass" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('admin_password'); ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-lock-password-line text-gray-400"></i>
                                </div>
                                <input type="password" id="admin_pass" name="admin_pass" value="<?php echo htmlspecialchars($form_data['ADMIN_PASSWORD'] ?? ''); ?>" class="w-full pl-10 border-gray-300 <?php echo isset($form_errors['ADMIN_PASSWORD']) ? 'border-red-300' : ''; ?> rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            <?php if (isset($form_errors['ADMIN_PASSWORD'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $form_errors['ADMIN_PASSWORD']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        <div class="flex items-center">
                            <i class="ri-information-line mr-1 text-indigo-500"></i>
                            <?php echo __('required_fields_note'); ?>
                        </div>
                    </div>
                    <button type="submit" name="action" value="install" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="ri-install-line mr-1"></i>
                        <?php echo __('install_now'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="container w-full max-w-screen-xl mx-auto px-4">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
                </div>
                <div class="text-sm text-gray-500">
                    <?php echo __('installer_version'); ?><?php echo INSTALLER_VERSION; ?>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
