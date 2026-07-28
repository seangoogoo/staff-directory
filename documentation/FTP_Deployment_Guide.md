# FTP Deployment Guide for Staff Directory Application

This guide provides step-by-step instructions for deploying the Staff Directory application on a remote server using FTP and phpMyAdmin.

## Prerequisites

- FTP access to your web server
- phpMyAdmin access to your MySQL database
- A web hosting account with PHP 7.4+ and MySQL 5.7+ support

## Step 1: Database Setup

### Option A: Using the Web Installer (Recommended)

1. Upload the application files to your server (see Step 3 for required files)
2. Navigate to the installer in your browser:
   - If installed in root: `https://your-domain.com/install.php`
   - If installed in subdirectory: `https://your-domain.com/staff-directory/install.php`
3. Follow the on-screen instructions to:
   - Configure your database connection
   - Set up your admin account
   - Initialize the database with the required tables
4. The installer will automatically create the database and tables for you
5. ⚠️ **Security — delete the installer:** as soon as the installation succeeds, delete `install.php` from the server (web root or subdirectory). It is **not** removed automatically.
6. Since this version the installer refuses every POST — connection test and install alike — with an HTTP 403 once `DB_INSTALLED=true` in `.env`. That is a safety net, **not** a reason to keep the file: the guard reads `DB_INSTALLED` from `staff_dir_env/.env`, so anything that makes that file unreadable or resets the flag reopens the installer to anyone, who could then overwrite your database and admin credentials. Delete it.

### Option B: Manual Database Setup

1. Log in to phpMyAdmin on your web server
2. Create a new database (e.g., `staff_dir`)
3. Select the newly created database
4. Click on the "Import" tab
5. Upload and import the `database/staff_dir_clean.sql` file
6. Verify that the tables have been created successfully

## Step 2: Prepare Configuration Files

Before uploading files, you need to configure the application:

1. Create a `.env` file in the `staff_dir_env` directory with the following content:
   ```
   # Database Configuration
   DB_HOST=localhost
   DB_USER=your_database_username
   DB_PASS=your_database_password
   DB_NAME=staff_dir
   DB_TABLE_PREFIX=
   DB_CREATE_DATABASE=true
   DB_INSTALLED=false

   # Admin Credentials
   ADMIN_USERNAME=your_admin_username
   ADMIN_PASSWORD_HASH=paste_the_generated_hash_here
   USE_SECURE_COOKIES=true

   # Session and Cookie Configuration
   SESSION_LIFETIME=86400
   SESSION_UPDATE_INTERVAL=3600
   COOKIE_PATH=/
   COOKIE_LIFETIME=2592000

   # Application Settings
   DEV_MODE=false

   # Language Configuration
   DEFAULT_LANGUAGE=en
   ```

2. **IMPORTANT**: Configure the application paths by editing the `public/includes/paths.local.php` file.

   The application uses a centralized path configuration system that makes installation easier. If the automatic path detection doesn't work correctly, you can customize the paths by:

   1. Copy `public/includes/paths.local.php.example` to `public/includes/paths.local.php`
   2. Edit the path constants in this file to match your server's directory structure

   ```php
   // Customize these paths based on your server's directory structure
   define('BASE_PATH', '/home/username/private'); // Project root directory
   define('PRIVATE_PATH', BASE_PATH); // Private files directory
   define('PUBLIC_PATH', '/home/username/public_html/staff-directory'); // Public web directory
   define('APP_BASE_URI', '/staff-directory'); // The base URI for routing
   ```

   **Explanation of each constant:**

   - `BASE_PATH`: The absolute path to your project's root directory where all private files are stored
   - `PRIVATE_PATH`: Usually the same as BASE_PATH, points to where private files are stored
   - `PUBLIC_PATH`: The absolute path to the public web directory (inside web root)
   - `APP_BASE_URI`: The URL path segment after your domain name (e.g., '/staff-directory' if accessed via example.com/staff-directory)

   **Examples for common hosting setups:**

   1. **Standard cPanel hosting** (public_html with subdirectory):
      ```php
      define('BASE_PATH', '/home/username/private'); // Private directory outside web root
      define('PRIVATE_PATH', BASE_PATH);
      define('PUBLIC_PATH', '/home/username/public_html/staff-directory'); // Inside web root
      define('APP_BASE_URI', '/staff-directory');
      ```

   2. **Subdomain installation**:
      ```php
      define('BASE_PATH', '/home/username/private'); // Private directory outside web root
      define('PRIVATE_PATH', BASE_PATH);
      define('PUBLIC_PATH', '/home/username/staff.example.com'); // Subdomain root
      define('APP_BASE_URI', ''); // Empty because it's at the root of the subdomain
      ```

## Step 3: Required Files and Directories to Upload

### Core Directories

Upload the following directories to your web server:

| Local Directory | Remote Directory | Description |
|-----------------|------------------|-------------|
| `config/` | `/path/to/your/private/config/` | Configuration files (outside web root) |
| `staff_dir_env/` | `/path/to/your/private/staff_dir_env/` | Environment variables (outside web root) |
| `vendor/` | `/path/to/your/private/vendor/` | Composer dependencies (outside web root) |
| `logs/` | `/path/to/your/private/logs/` | Application logs (outside web root) |
| `languages/` | `/path/to/your/private/languages/` | Translation files (outside web root) |
| `database/` | `/path/to/your/private/database/` | SQL files and database utilities (outside web root) |
| `tools/` | `/path/to/your/private/tools/` | Maintenance CLI scripts, only needed for a migration (outside web root) |
| `public/` | `/path/to/your/public_html/` | Public web files (in web root) |

### Required Files

Make sure these specific files are included:

1. **Configuration Files**:
   - `config/auth_config.php`
   - `config/database.php`
   - `config/env_loader.php`
   - `config/languages.php`
   - `staff_dir_env/.env` (create this file as described above)
   - `languages/en/` (English translation files)
   - `languages/fr/` (French translation files)
   - `database/staff_dir_clean.sql` (for manual database setup)
   - `database/process_sql.php` (for the installer)
   - `database/migrate_tables.php` (for table prefix migration)
   - `tools/hash_admin_password.php` (only when updating a deployment whose `.env` still holds a cleartext `ADMIN_PASSWORD`)

2. **Core Application Files**:
   - `public/front-controller.php`
   - `public/index.php`
   - `public/install.php` (web-based installer)
   - `public/includes/bootstrap.php`
   - `public/includes/paths.php`
   - `public/includes/Router.php`
   - `public/includes/MiddlewareStack.php`
   - `public/includes/functions.php`
   - `public/.htaccess`

3. **Upload Directories** (create these if they don't exist):
   - `public/uploads/companies/`
   - `public/uploads/logos/`
   - `public/uploads/placeholders/`

## Step 4: Set Directory Permissions

Set the correct permissions for the following directories:

```
chmod 755 /path/to/your/public_html/staff-directory
chmod 755 /path/to/your/private/config
chmod 755 /path/to/your/private/staff_dir_env
chmod 755 /path/to/your/private/logs
chmod 755 /path/to/your/private/vendor
chmod 755 /path/to/your/private/languages
chmod 755 /path/to/your/private/database
chmod 775 /path/to/your/public_html/uploads
chmod 775 /path/to/your/public_html/uploads/companies
chmod 775 /path/to/your/public_html/uploads/logos
chmod 775 /path/to/your/public_html/uploads/placeholders
```

## Step 5: Using the Web Installer

The web installer (`install.php`) is the recommended way to set up your database. It requires:

1. **Database Directory**: The `database/` directory must be properly uploaded and accessible
   - This directory contains SQL files and processing scripts needed by the installer
   - If the installer fails with "SQL file not found" errors, check that this directory exists and has the correct permissions

2. **Running the Installer**:
   - Navigate to the installer:
     - Root installation: `https://your-domain.com/install.php`
     - Subdirectory installation: `https://your-domain.com/staff-directory/install.php`
   - Fill in your database credentials
   - Set your admin username and password
   - Choose whether to include example data
   - Click "Install Now" to complete the setup
   - ⚠️ **Security — delete the installer:** once the installer reports success, delete `install.php` from the server. It is **not** removed automatically. Only re-upload it if you deliberately need to reinstall.
   - Since this version, once `DB_INSTALLED=true`, the installer rejects every POST with an HTTP 403 — the connection test included — while still showing the "already installed" screen on GET. It is defense in depth, not a replacement for deleting the file: the check reads `DB_INSTALLED` from `staff_dir_env/.env`, so a missing or unreadable `.env` reopens the installer. To reinstall deliberately, see the "Already installed message" entry below, which lifts both the screen and the 403.

3. **Common Installer Issues**:
   - **"SQL processor not found" error**: Check that the `database/process_sql.php` file exists and is readable
   - **"SQL file not found" error**: Check that the `database/staff_dir_clean.sql` and `database/staff_dir.sql` files exist
   - **Database creation fails**: Ensure your database user has CREATE DATABASE privileges
   - **Connection test fails**: Verify database credentials (host, username, password)
   - **Permission errors**: Ensure the web server can write to `staff_dir_env/.env` file
   - **Already installed message**: If you need to reinstall, set `DB_INSTALLED=false` in `.env` file

4. **Manual Installation Fallback**:
   If the web installer fails, you can install manually:
   1. Use Option B (Manual Database Setup) above to create the database
   2. Manually edit `staff_dir_env/.env` file:
      - Set database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
      - Set `DB_TABLE_PREFIX=sd_` (or your preferred prefix)
      - Set `DB_INSTALLED=true`
      - Set `ADMIN_USERNAME`
      - Set `ADMIN_PASSWORD_HASH` — the admin password is stored **only as a hash**, never in
        cleartext. Generate one locally and paste the result:
        ```bash
        php -r 'echo password_hash("your-password", PASSWORD_DEFAULT), PHP_EOL;'
        ```
        Write the password down: it cannot be recovered from `.env`. If you are updating an
        older deployment whose `.env` still has a cleartext `ADMIN_PASSWORD`, upload the
        `tools/` directory and run `php tools/hash_admin_password.php` on the server instead —
        it hashes the existing password in place, after a timestamped backup. A `.env` left
        with only `ADMIN_PASSWORD` can no longer log in.
   3. Navigate to your website to verify it works

## Step 6: Verify Installation

1. Navigate to your website:
   - Root installation: `https://your-domain.com/`
   - Subdirectory installation: `https://your-domain.com/staff-directory/`
2. You should see the staff directory homepage
3. Try accessing the admin area:
   - Root installation: `https://your-domain.com/admin/`
   - Subdirectory installation: `https://your-domain.com/staff-directory/admin/`
4. Log in with the admin credentials you set in the `.env` file or during installation

## Troubleshooting

### File Permissions Issues

If you encounter "Permission denied" errors:
- Ensure upload directories are writable (chmod 775)
- Check that the web server user has read access to configuration files

### Database Connection Issues

If you see database connection errors:
- Verify your database credentials in the `.env` file
- Check that the database exists and tables are created
- Ensure the database user has the necessary permissions
- If using table prefixes, make sure all queries use the correct prefix

### Table Prefix Issues

If you're using table prefixes and encounter issues:
- Check that the `DB_TABLE_PREFIX` setting in your `.env` file is correct
- Verify that the tables in your database have the correct prefix
- If migrating an existing installation, run the migration script:
  ```bash
  php database/migrate_tables.php
  ```
- If the migration fails, check the error messages and ensure you have proper database permissions

### Path Configuration Issues

If links or assets are not loading correctly, or you see "file not found" errors:

- **Path Configuration**: This is the most common source of deployment issues
  - The application now uses a centralized path configuration system in `public/includes/paths.php`
  - If automatic path detection doesn't work, create and edit `paths.local.php`:
    1. Copy `public/includes/paths.local.php.example` to `paths.local.php`
    2. Edit the path constants to match your server's directory structure
    3. Use absolute paths (starting with `/`) rather than relative paths

  **Typical Directory Structure (Subdirectory Installation Example):**
  ```
  /home/username/                  <- Server root
  ├── private/                     <- BASE_PATH & PRIVATE_PATH (outside web root)
  │   ├── config/
  │   ├── database/                <- Required for installer
  │   ├── languages/
  │   ├── logs/
  │   ├── staff_dir_env/
  │   └── vendor/
  │
  └── public_html/                 <- Web root
      └── staff-directory/         <- APP_BASE_URI='/staff-directory' & PUBLIC_PATH
          ├── admin/
          ├── assets/
          ├── includes/
          │   ├── bootstrap.php
          │   ├── paths.php        <- Centralized path configuration
          │   └── paths.local.php  <- Your custom path settings (if needed)
          ├── uploads/
          ├── .htaccess
          ├── front-controller.php
          ├── index.php
          └── install.php
  ```

  - If you're still having issues, you can enable debug logging in `paths.local.php`:
    ```php
    // Add this to paths.local.php to debug paths
    error_log('Current directory: ' . __DIR__);
    error_log('Configured paths:');
    error_log('BASE_PATH: ' . BASE_PATH);
    error_log('PUBLIC_PATH: ' . PUBLIC_PATH);
    error_log('APP_BASE_URI: ' . APP_BASE_URI);
    ```

- **Other common issues**:
  - Verify that the `.htaccess` file was uploaded correctly
  - Make sure your web server has mod_rewrite enabled
  - Check file permissions on all directories

### 404 Errors

If you get 404 errors when accessing pages:
- Check that the front-controller.php file exists
- Verify that the .htaccess file is correctly configured
- Make sure your web server supports .htaccess files

## Security Considerations

1. Keep the `staff_dir_env/` directory outside the web root
2. Use strong passwords for the admin account
3. Set `DEV_MODE=false` in production
4. Consider enabling HTTPS for your domain
5. Regularly backup your database and files
