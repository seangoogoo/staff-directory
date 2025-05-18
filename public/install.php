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
define('INSTALLER_VERSION', '1.0.0');
define('APP_NAME', 'Staff Directory');
define('ENV_FILE_PATH', __DIR__ . '/../staff_dir_env/.env');
define('ENV_EXAMPLE_PATH', __DIR__ . '/../staff_dir_env/.env_example');
define('SQL_CLEAN_PATH', __DIR__ . '/../database/staff_dir_clean.sql');
define('SQL_PROCESSOR_PATH', __DIR__ . '/../database/process_sql.php');

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

// Test database connection
function test_db_connection($host, $user, $pass, $name = '') {
    $conn = @new mysqli($host, $user, $pass, $name);
    
    if ($conn->connect_error) {
        return [
            'success' => false,
            'message' => "Connection failed: " . $conn->connect_error
        ];
    }
    
    return [
        'success' => true,
        'message' => "Connection successful!"
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
function initialize_database($host, $user, $pass, $name, $prefix, $create_db) {
    // First, test the connection
    $test = test_db_connection($host, $user, $pass);
    if (!$test['success']) {
        return $test;
    }
    
    // Create database if needed
    if ($create_db) {
        $conn = new mysqli($host, $user, $pass);
        $sql = "CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($name) . "`";
        if (!$conn->query($sql)) {
            return [
                'success' => false,
                'message' => "Error creating database: " . $conn->error
            ];
        }
        $conn->close();
    }
    
    // Test connection with database name
    $test = test_db_connection($host, $user, $pass, $name);
    if (!$test['success']) {
        return $test;
    }
    
    // Process the SQL file
    if (!file_exists(SQL_CLEAN_PATH)) {
        return [
            'success' => false,
            'message' => "SQL file not found: " . SQL_CLEAN_PATH
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
    
    // Include the processor and process the SQL file
    require_once SQL_PROCESSOR_PATH;
    $result = process_sql_file(SQL_CLEAN_PATH, $temp_sql, $name, $prefix, $create_db);
    
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data = [
        'DB_HOST' => $_POST['db_host'] ?? 'localhost',
        'DB_USER' => $_POST['db_user'] ?? '',
        'DB_PASS' => $_POST['db_pass'] ?? '',
        'DB_NAME' => $_POST['db_name'] ?? 'staff_dir',
        'DB_TABLE_PREFIX' => $_POST['db_prefix'] ?? '',
        'DB_CREATE_DATABASE' => isset($_POST['db_create']) ? 'true' : 'false',
        'ADMIN_USERNAME' => $_POST['admin_user'] ?? 'admin',
        'ADMIN_PASSWORD' => $_POST['admin_pass'] ?? 'admin'
    ];
    
    // Check action
    $action = $_POST['action'] ?? '';
    
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
        // Validate required fields
        $required = ['DB_HOST', 'DB_USER', 'DB_NAME', 'ADMIN_USERNAME', 'ADMIN_PASSWORD'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($form_data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $message = "Missing required fields: " . implode(', ', $missing);
            $message_type = 'error';
        } else {
            // Initialize database
            $result = initialize_database(
                $form_data['DB_HOST'],
                $form_data['DB_USER'],
                $form_data['DB_PASS'],
                $form_data['DB_NAME'],
                $form_data['DB_TABLE_PREFIX'],
                $form_data['DB_CREATE_DATABASE'] === 'true'
            );
            
            if ($result['success']) {
                // Update .env file with all settings
                $form_data['DB_INSTALLED'] = 'true';
                $update_result = update_env_file($form_data);
                
                if ($update_result) {
                    $message = "Installation completed successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Database initialized but failed to update .env file.";
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
    $form_data['DB_TABLE_PREFIX'] = '';
    $form_data['DB_CREATE_DATABASE'] = 'true';
    $form_data['ADMIN_USERNAME'] = 'admin';
}

// HTML output starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> Installer</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .installer-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .message.error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="installer-container bg-white shadow-md rounded-lg">
        <h1 class="text-2xl font-bold mb-6 text-center text-indigo-700"><?php echo APP_NAME; ?> Installer</h1>
        
        <?php if ($installed): ?>
            <div class="message success">
                <p><strong>Application is already installed!</strong></p>
                <p>If you want to reinstall, please delete the DB_INSTALLED=true line from your .env file.</p>
                <p class="mt-4"><a href="index.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Go to Application</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="install.php" class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-md">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">Database Configuration</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="db_host" class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                            <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($form_data['DB_HOST'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="db_name" class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                            <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($form_data['DB_NAME'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="db_user" class="block text-sm font-medium text-gray-700 mb-1">Database User</label>
                            <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($form_data['DB_USER'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($form_data['DB_PASS'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="db_prefix" class="block text-sm font-medium text-gray-700 mb-1">Table Prefix (Optional)</label>
                            <input type="text" id="db_prefix" name="db_prefix" value="<?php echo htmlspecialchars($form_data['DB_TABLE_PREFIX'] ?? ''); ?>" placeholder="e.g., sd_" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="db_create" name="db_create" <?php echo (($form_data['DB_CREATE_DATABASE'] ?? 'true') === 'true') ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="db_create" class="ml-2 block text-sm text-gray-700">Create database if it doesn't exist</label>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="action" value="test_connection" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Test Connection</button>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">Admin Account</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="admin_user" class="block text-sm font-medium text-gray-700 mb-1">Admin Username</label>
                            <input type="text" id="admin_user" name="admin_user" value="<?php echo htmlspecialchars($form_data['ADMIN_USERNAME'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="admin_pass" class="block text-sm font-medium text-gray-700 mb-1">Admin Password</label>
                            <input type="password" id="admin_pass" name="admin_pass" value="<?php echo htmlspecialchars($form_data['ADMIN_PASSWORD'] ?? ''); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="action" value="install" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">Install</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p><?php echo APP_NAME; ?> Installer v<?php echo INSTALLER_VERSION; ?></p>
        </div>
    </div>
</body>
</html>
