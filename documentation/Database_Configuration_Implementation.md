# Database Configuration and Installer Implementation Guide

This document provides detailed implementation steps for adding custom database configuration and an installer to the Staff Directory application.

## 1. Environment Configuration

### 1.1 Update `.env_example` file

Update the database configuration in `staff_dir_env/.env_example`:

```
# Database Configuration
DB_HOST=localhost
DB_USER=your_database_username
DB_PASS=your_database_password
DB_NAME=staff_dir                # Can be changed to use an existing database
DB_TABLE_PREFIX=                 # Optional, add prefix to all tables (e.g., sd_)
DB_CREATE_DATABASE=true          # Set to false if the database already exists
```

### 1.2 Update your actual `.env` file

Add the custom database configuration to your environment file:

```
# Database Configuration
DB_HOST=localhost
DB_USER=your_database_username
DB_PASS=your_database_password
DB_NAME=existing_database_name   # Name of your existing database
DB_TABLE_PREFIX=sd_              # Prefix for all application tables
DB_CREATE_DATABASE=false         # Don't create database as it already exists
```

## 2. Database Configuration Updates

### 2.1 Modify `config/database.php`

Update the `config/database.php` file with the following changes:

```php
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
```

## 3. SQL Schema Updates

### 3.1 Create a SQL Processor Script

Create a new file `database/process_sql.php`:

```php
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
$db_name = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'staff_dir';
$prefix = isset($_ENV['DB_TABLE_PREFIX']) ? $_ENV['DB_TABLE_PREFIX'] : '';
$create_db = isset($_ENV['DB_CREATE_DATABASE']) ?
    (strtolower($_ENV['DB_CREATE_DATABASE']) === 'true') : true;

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
    $processed_sql = str_replace(
        ['{DB_NAME}', '{PREFIX}'],
        [$db_name, $prefix],
        $sql
    );

    // Write to output file
    if (file_put_contents($output_file, $processed_sql) === false) {
        echo "Error: Could not write to output file: $output_file\n";
        return false;
    }

    return $output_file;
}

// Command line interface
if (PHP_SAPI === 'cli') {
    // Check if file argument is provided
    if ($argc < 2) {
        echo "Usage: php process_sql.php <sql_file> [output_file]\n";
        exit(1);
    }

    $input_file = $argv[1];
    $output_file = isset($argv[2]) ? $argv[2] : null;

    // Process the SQL file
    $result = process_sql_file($input_file, $output_file, $db_name, $prefix, $create_db);
    if ($result) {
        echo "SQL file processed successfully. Output: $result\n";
        exit(0);
    } else {
        echo "Failed to process SQL file.\n";
        exit(1);
    }
}
```

### 3.2 Update SQL Schema Files

Modify `database/staff_dir.sql` and `database/staff_dir_clean.sql` to use the database name and table prefix placeholders:

```sql
-- Example for staff_dir.sql
-- {DB_CREATE}
CREATE DATABASE IF NOT EXISTS `{DB_NAME}`;
-- {/DB_CREATE}
USE `{DB_NAME}`;

-- Companies Table
CREATE TABLE IF NOT EXISTS `{PREFIX}companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `logo` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert example companies
INSERT INTO `{PREFIX}companies` (`name`, `description`, `logo`) VALUES
('NeuroSoft GmbH', 'Leading software development company...', '/uploads/companies/software-company.svg'),
-- other values remain the same
```

Apply similar changes to all table definitions and queries in the SQL files, ensuring all table references use the `{PREFIX}` placeholder and all database references use the `{DB_NAME}` placeholder.

## 4. Code Updates

### 4.1 Update SQL Queries in Functions

Modify SQL queries in `public/includes/functions.php` to use the table constants:

```php
// Before
$sql = "SELECT * FROM companies WHERE id = ?";

// After
$sql = "SELECT * FROM " . TABLE_COMPANIES . " WHERE id = ?";
```

For JOIN queries:

```php
// Before
$sql = "SELECT s.*, d.name as department, d.color as department_color, "
     . "c.name as company, c.id as company_id, c.logo as company_logo "
     . "FROM staff_members s "
     . "JOIN departments d ON s.department_id = d.id "
     . "JOIN companies c ON s.company_id = c.id "
     . "WHERE 1=1";

// After
$sql = "SELECT s.*, d.name as department, d.color as department_color, "
     . "c.name as company, c.id as company_id, c.logo as company_logo "
     . "FROM " . TABLE_STAFF_MEMBERS . " s "
     . "JOIN " . TABLE_DEPARTMENTS . " d ON s.department_id = d.id "
     . "JOIN " . TABLE_COMPANIES . " c ON s.company_id = c.id "
     . "WHERE 1=1";
```

### 4.2 Update Admin Pages

Apply similar changes to all SQL queries in admin pages:

- `public/admin/add.php`
- `public/admin/edit.php`
- `public/admin/delete.php`
- `public/admin/departments.php`
- `public/admin/companies.php`
- `public/admin/settings.php`

## 5. Database Migration Script

Create a migration script to rename existing tables with the new prefix:

```php
<?php
/**
 * Database Migration Script for Table Prefixing
 *
 * This script renames existing tables to add the configured prefix.
 */

// Load environment variables and database connection
require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/database.php';

// Check if prefix is defined
if (empty(DB_TABLE_PREFIX)) {
    echo "No prefix defined. Nothing to migrate.\n";
    exit(0);
}

// Tables to migrate
$tables = [
    'companies',
    'departments',
    'staff_members',
    'app_settings'
];

// Perform migration
foreach ($tables as $table) {
    $old_name = $table;
    $new_name = DB_TABLE_PREFIX . $table;

    // Check if old table exists
    $result = $conn->query("SHOW TABLES LIKE '$old_name'");
    if ($result->num_rows == 0) {
        echo "Table '$old_name' does not exist. Skipping.\n";
        continue;
    }

    // Check if new table already exists
    $result = $conn->query("SHOW TABLES LIKE '$new_name'");
    if ($result->num_rows > 0) {
        echo "Table '$new_name' already exists. Skipping.\n";
        continue;
    }

    // Rename table
    $sql = "RENAME TABLE `$old_name` TO `$new_name`";
    if ($conn->query($sql) === TRUE) {
        echo "Table '$old_name' renamed to '$new_name' successfully.\n";
    } else {
        echo "Error renaming table '$old_name': " . $conn->error . "\n";
    }
}

echo "Migration completed.\n";
```

## 6. Web-Based Installer

Create a new file `public/install.php` for the web-based installer:

```php
<?php
/**
 * Staff Directory Installer
 *
 * This script provides a web-based installer for the Staff Directory application.
 * It allows configuring database settings and installing the application.
 */

// Define constants for installation
define('INSTALL_PATH', __DIR__);
define('BASE_PATH', dirname(__DIR__));
define('ENV_PATH', BASE_PATH . '/staff_dir_env');
define('ENV_FILE', ENV_PATH . '/.env');
define('SQL_FILE', BASE_PATH . '/database/staff_dir_clean.sql');

// Include installer functions from secure config directory
require_once dirname(__DIR__) . '/config/installer.php';

// Check if already installed
if (is_installed() && !isset($_GET['force'])) {
    die("The application appears to be already installed. If you want to reinstall, add ?force=1 to the URL.");
}

// Process form submission
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'staff_dir';
    $db_prefix = $_POST['db_prefix'] ?? '';
    $create_db = isset($_POST['create_db']) && $_POST['create_db'] === '1';
    $admin_user = $_POST['admin_user'] ?? 'admin';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $remove_installer = isset($_POST['remove_installer']) && $_POST['remove_installer'] === '1';

    // Validate input
    if (empty($db_user) || empty($db_name) || empty($admin_pass)) {
        $message = "Error: Required fields are missing.";
    } else {
        // Try to install
        $result = install_application(
            $db_host, $db_user, $db_pass, $db_name,
            $db_prefix, $create_db, $admin_user, $admin_pass
        );

        if ($result['success']) {
            $success = true;
            $message = "Installation completed successfully!";

            // Remove installer if requested
            if ($remove_installer) {
                // Schedule deletion after response is sent
                register_shutdown_function(function() {
                    @unlink(__FILE__);
                });
                $message .= " The installer will be removed.";
            }
        } else {
            $message = "Error: " . $result['message'];
        }
    }
}

// HTML for the installer page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Directory Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 2rem; }
        .installer-container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container installer-container">
        <h1 class="mb-4">Staff Directory Installer</h1>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
            <p class="mt-3">
                <a href="index.php" class="btn btn-primary">Go to Application</a>
                <a href="admin/index.php" class="btn btn-secondary">Go to Admin Panel</a>
            </p>
        </div>
        <?php elseif (!empty($message)): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Database Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="staff_dir" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="db_prefix" class="form-label">Table Prefix (Optional)</label>
                            <input type="text" class="form-control" id="db_prefix" name="db_prefix" placeholder="e.g., sd_">
                            <div class="form-text">Add a prefix to all tables to avoid conflicts with existing tables.</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="create_db" name="create_db" value="1" checked>
                                <label class="form-check-label" for="create_db">
                                    Create database if it doesn't exist
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Admin Account</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="admin_user" class="form-label">Admin Username</label>
                            <input type="text" class="form-control" id="admin_user" name="admin_user" value="admin" required>
                        </div>
                        <div class="col-md-6">
                            <label for="admin_pass" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Installation Options</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remove_installer" name="remove_installer" value="1" checked>
                        <label class="form-check-label" for="remove_installer">
                            Remove installer after successful installation (recommended for security)
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Install Application</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

Create the installer helper functions in `config/installer.php` (secure, non-public location):

```php
<?php
/**
 * Installer Helper Functions
 */

/**
 * Check if the application is already installed
 *
 * @return bool True if installed, false otherwise
 */
function is_installed() {
    // Check if .env file exists
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    // Try to connect to the database using current settings
    try {
        // Load environment variables
        $env_content = file_get_contents(ENV_FILE);
        preg_match('/DB_HOST=(.*)/', $env_content, $host_matches);
        preg_match('/DB_USER=(.*)/', $env_content, $user_matches);
        preg_match('/DB_PASS=(.*)/', $env_content, $pass_matches);
        preg_match('/DB_NAME=(.*)/', $env_content, $name_matches);

        $host = isset($host_matches[1]) ? trim($host_matches[1]) : 'localhost';
        $user = isset($user_matches[1]) ? trim($user_matches[1]) : '';
        $pass = isset($pass_matches[1]) ? trim($pass_matches[1]) : '';
        $name = isset($name_matches[1]) ? trim($name_matches[1]) : '';

        if (empty($user) || empty($name)) {
            return false;
        }

        // Try to connect
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            return false;
        }

        // Check if at least one table exists
        $prefix = '';
        preg_match('/DB_TABLE_PREFIX=(.*)/', $env_content, $prefix_matches);
        if (isset($prefix_matches[1])) {
            $prefix = trim($prefix_matches[1]);
        }

        $result = $conn->query("SHOW TABLES LIKE '{$prefix}companies'");
        $installed = ($result && $result->num_rows > 0);

        $conn->close();
        return $installed;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Install the application
 *
 * @param string $db_host Database host
 * @param string $db_user Database username
 * @param string $db_pass Database password
 * @param string $db_name Database name
 * @param string $db_prefix Table prefix
 * @param bool $create_db Whether to create the database
 * @param string $admin_user Admin username
 * @param string $admin_pass Admin password
 * @return array Result with success status and message
 */
function install_application($db_host, $db_user, $db_pass, $db_name, $db_prefix, $create_db, $admin_user, $admin_pass) {
    try {
        // 1. Test database connection
        $conn = new mysqli($db_host, $db_user, $db_pass);
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }

        // 2. Create database if requested
        if ($create_db) {
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`")) {
                return [
                    'success' => false,
                    'message' => "Failed to create database: " . $conn->error
                ];
            }
        }

        // Select the database
        $conn->select_db($db_name);

        // 3. Process SQL file
        $sql_content = file_get_contents(SQL_FILE);
        if ($sql_content === false) {
            return [
                'success' => false,
                'message' => "Could not read SQL file: " . SQL_FILE
            ];
        }

        // Process DB_CREATE sections
        if (!$create_db) {
            $sql_content = preg_replace('/-- {DB_CREATE}.*?-- {\/DB_CREATE}/s', '', $sql_content);
        } else {
            $sql_content = str_replace(['-- {DB_CREATE}', '-- {/DB_CREATE}'], '', $sql_content);
        }

        // Replace placeholders
        $sql_content = str_replace(
            ['{DB_NAME}', '{PREFIX}'],
            [$db_name, $db_prefix],
            $sql_content
        );

        // Split SQL into individual statements
        $statements = explode(';', $sql_content);

        // Execute each statement
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                if (!$conn->query($statement)) {
                    return [
                        'success' => false,
                        'message' => "SQL execution failed: " . $conn->error . " in statement: " . substr($statement, 0, 100) . "..."
                    ];
                }
            }
        }

        // 4. Create .env file
        $env_content = "# Database Configuration\n"
                     . "DB_HOST=$db_host\n"
                     . "DB_USER=$db_user\n"
                     . "DB_PASS=$db_pass\n"
                     . "DB_NAME=$db_name\n"
                     . "DB_TABLE_PREFIX=$db_prefix\n"
                     . "DB_CREATE_DATABASE=$create_db\n\n"
                     . "# Admin Credentials\n"
                     . "ADMIN_USERNAME=$admin_user\n"
                     . "ADMIN_PASSWORD=$admin_pass\n"
                     . "USE_SECURE_COOKIES=true\n\n"
                     . "# Session and Cookie Configuration\n"
                     . "SESSION_LIFETIME=86400\n"
                     . "SESSION_UPDATE_INTERVAL=3600\n"
                     . "COOKIE_PATH=/\n"
                     . "COOKIE_LIFETIME=2592000\n\n"
                     . "# Application Settings\n"
                     . "DEV_MODE=false\n";

        // Ensure directory exists
        if (!is_dir(ENV_PATH)) {
            mkdir(ENV_PATH, 0755, true);
        }

        // Write .env file
        if (file_put_contents(ENV_FILE, $env_content) === false) {
            return [
                'success' => false,
                'message' => "Failed to write .env file. Please check permissions."
            ];
        }

        // 5. Close connection
        $conn->close();

        return [
            'success' => true,
            'message' => "Installation completed successfully."
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Installation failed: " . $e->getMessage()
        ];
    }
}
```

## 7. Testing

1. Test with default database name and empty prefix to ensure backward compatibility
2. Test with custom database name and prefix to ensure proper functionality
3. Test the installer with various configuration options:
   - With and without database creation
   - With and without table prefix
   - With different admin credentials

## 8. Documentation Updates

### 8.1 Update README.md

Add information about the database configuration and installer to the README.md file:

```markdown
### Database Configuration

The Staff Directory application supports custom database configuration:

- **Custom Database Name**: You can use an existing database instead of creating a new one
- **Table Prefixing**: Add a prefix to all tables to avoid conflicts with existing tables
- **Web-Based Installer**: Easy setup through a user-friendly web interface

### Installation Options

#### Using the Web Installer (Recommended)

1. Upload the application files to your web server
2. Navigate to `https://your-domain.com/install.php` in your browser
3. Follow the on-screen instructions to configure your database and admin account
4. After successful installation, the installer will be removed automatically

#### Manual Installation

1. Create a `.env` file in the `staff_dir_env` directory with your database settings:
   ```
   DB_HOST=localhost
   DB_USER=your_database_username
   DB_PASS=your_database_password
   DB_NAME=your_database_name
   DB_TABLE_PREFIX=your_prefix_  # Optional, e.g., sd_
   DB_CREATE_DATABASE=false      # Set to true if the database should be created
   ```

2. Process and import the SQL schema:
   ```
   php database/process_sql.php database/staff_dir_clean.sql processed.sql
   mysql -u username -p your_database_name < processed.sql
   ```
```

### 8.2 Update devbook.md

Add technical details about the implementation to the devbook.md file:

```markdown
## Database Configuration

### Custom Database Support

The application has been enhanced to support:

- Custom database names through the `DB_NAME` environment variable
- Table prefixing through the `DB_TABLE_PREFIX` environment variable
- Optional database creation through the `DB_CREATE_DATABASE` environment variable

### Implementation Details

- Table names are defined as constants in `config/database.php`:
  ```php
  define('TABLE_COMPANIES', DB_TABLE_PREFIX . 'companies');
  define('TABLE_DEPARTMENTS', DB_TABLE_PREFIX . 'departments');
  define('TABLE_STAFF_MEMBERS', DB_TABLE_PREFIX . 'staff_members');
  define('TABLE_APP_SETTINGS', DB_TABLE_PREFIX . 'app_settings');
  ```

- SQL queries use these constants instead of hardcoded table names
- SQL schema files use placeholders (`{DB_NAME}`, `{PREFIX}`) that are replaced during installation
- The database connection logic has been updated to support connecting to existing databases

### Web-Based Installer

The application includes a web-based installer (`public/install.php`) that:

- Provides a user-friendly interface for database configuration
- Processes SQL schema files with the correct database name and table prefixes
- Creates the `.env` file with the provided settings
- Can be configured to self-delete after successful installation

For security, the installer helper functions are located in the non-public `config/installer.php` file.
```

### 8.3 Update FTP_Deployment_Guide.md

Update the deployment guide with information about the installer:

```markdown
## Installation Options

### Using the Web Installer (Recommended)

After uploading the files to your web server:

1. Navigate to `https://your-domain.com/install.php` in your browser
2. Fill in the database configuration:
   - Database Host (usually "localhost")
   - Database Name (existing database to use)
   - Database Username and Password
   - Table Prefix (optional, e.g., "sd_")
   - Check "Create database" if you want a new database to be created
3. Set up your admin account
4. Click "Install Application"
5. After successful installation, you'll be redirected to the application

### Manual Installation

If you prefer manual installation:

1. Create a `.env` file in the `staff_dir_env` directory with your database settings
2. Process the SQL schema file using the provided script
3. Import the processed SQL file into your database
```

## 9. Deployment Instructions

### 9.1 Manual Deployment

1. Update your `.env` file with the desired database name and table prefix
2. If installing on a new database:
   - Process the SQL schema file: `php database/process_sql.php database/staff_dir.sql`
   - Import the processed SQL file into your database
3. If migrating an existing installation:
   - Run the migration script: `php database/migrate_prefix.php`

### 9.2 Using the Web Installer

1. Upload the application files to your web server
2. Navigate to `https://your-domain.com/install.php` in your browser
3. Fill in the database configuration and admin credentials
4. Click "Install Application"
5. After successful installation, the installer will be removed automatically (if selected)

## Conclusion

By following this implementation guide, you can add custom database configuration and an installer to the Staff Directory application. This will allow the application to be easily deployed in various environments, including shared database servers with existing tables. The updated documentation will ensure that users and developers understand how to use these new features.
