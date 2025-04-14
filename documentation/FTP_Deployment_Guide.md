# FTP Deployment Guide for Staff Directory Application

This guide provides step-by-step instructions for deploying the Staff Directory application on a remote server using FTP and phpMyAdmin.

## Prerequisites

- FTP access to your web server
- phpMyAdmin access to your MySQL database
- A web hosting account with PHP 7.4+ and MySQL 5.7+ support

## Step 1: Database Setup

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

   # Admin Credentials
   ADMIN_USERNAME=your_admin_username
   ADMIN_PASSWORD=your_admin_password
   USE_SECURE_COOKIES=true

   # Session and Cookie Configuration
   SESSION_LIFETIME=86400
   SESSION_UPDATE_INTERVAL=3600
   COOKIE_PATH=/
   COOKIE_LIFETIME=2592000

   # Application Settings
   DEV_MODE=false
   ```

2. Update the `public/includes/bootstrap.php` file to match your server's directory structure:
   ```php
   // Adjust these paths based on your server's directory structure
   define('BASE_PATH', dirname(__DIR__, 3)); // Project root
   define('PRIVATE_PATH', BASE_PATH); // Private files directory
   define('PUBLIC_PATH', BASE_PATH . '/public/staff-directory'); // Path to the public directory
   define('APP_BASE_URI', '/staff-directory'); // The base URI for routing
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
| `public/staff-directory/` | `/path/to/your/public_html/staff-directory/` | Public web files (in web root) |

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

2. **Core Application Files**:
   - `public/staff-directory/front-controller.php`
   - `public/staff-directory/includes/bootstrap.php`
   - `public/staff-directory/includes/Router.php`
   - `public/staff-directory/includes/MiddlewareStack.php`
   - `public/staff-directory/includes/functions.php`
   - `public/staff-directory/.htaccess`

3. **Upload Directories** (create these if they don't exist):
   - `public/staff-directory/uploads/companies/`
   - `public/staff-directory/uploads/logos/`
   - `public/staff-directory/uploads/placeholders/`

## Step 4: Set Directory Permissions

Set the correct permissions for the following directories:

```
chmod 755 /path/to/your/public_html/staff-directory
chmod 755 /path/to/your/private/config
chmod 755 /path/to/your/private/staff_dir_env
chmod 755 /path/to/your/private/logs
chmod 755 /path/to/your/private/vendor
chmod 755 /path/to/your/private/languages
chmod 775 /path/to/your/public_html/staff-directory/uploads
chmod 775 /path/to/your/public_html/staff-directory/uploads/companies
chmod 775 /path/to/your/public_html/staff-directory/uploads/logos
chmod 775 /path/to/your/public_html/staff-directory/uploads/placeholders
```

## Step 5: Verify Installation

1. Navigate to your website: `https://your-domain.com/staff-directory/`
2. You should see the staff directory homepage
3. Try accessing the admin area: `https://your-domain.com/staff-directory/admin/`
4. Log in with the admin credentials you set in the `.env` file

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

### Path Configuration Issues

If links or assets are not loading correctly:
- Check the `APP_BASE_URI` setting in `bootstrap.php`
- Verify that the `.htaccess` file was uploaded correctly
- Make sure your web server has mod_rewrite enabled

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
