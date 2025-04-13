# Subdirectory Deployment Configuration Checklist

This document provides a comprehensive, beginner-friendly guide for deploying the Staff Directory application in a subdirectory (e.g., `https://example.com/staff-directory/` instead of directly at `https://example.com/`).

## What is a Subdirectory Deployment?

A subdirectory deployment means your application lives in a folder within your website rather than at the root level. For example:
- Root deployment: `https://staffdirectory.com/`
- Subdirectory deployment: `https://yourcompany.com/staff-directory/`

Subdirectory deployments are common when you want to add the Staff Directory as one section of an existing website.

## Step 1: Prepare Your Server Environment

### 1.1 Create the Subdirectory

- [ ] Create a new folder in your web server's document root where you want to install the Staff Directory
  ```bash
  # Example for a typical Apache setup
  mkdir /var/www/html/staff-directory
  ```

- [ ] Copy all the files from the `public/staff-directory` folder of the application to your new subdirectory
  ```bash
  # Example
  cp -r /path/to/staff-directory-project/public/staff-directory/* /var/www/html/staff-directory/
  ```

- [ ] Make sure the non-public folders (like `config/`) are placed outside the web-accessible directory for security
  ```bash
  # Example
  mkdir /var/www/staff-directory-private
  cp -r /path/to/staff-directory-project/config /var/www/staff-directory-private/
  ```

### 1.2 Set File Permissions

- [ ] Ensure the web server has read access to all files and directories
  ```bash
  # Example for Apache on Linux
  chown -R www-data:www-data /var/www/html/staff-directory
  chmod -R 755 /var/www/html/staff-directory
  ```

- [ ] Make sure upload directories are writable by the web server
  ```bash
  chmod -R 775 /var/www/html/staff-directory/uploads
  ```

## Step 2: Configure the Application for Subdirectory

### 2.1 Update Path Configuration in bootstrap.php

- [ ] Open the `public/includes/bootstrap.php` file and carefully update the path definitions to match your subdirectory structure:

  ```php
  if (!defined('BASE_PATH')) {
      // IMPORTANT: These paths must be configured correctly for your server setup

      // BASE_PATH should point to your project root directory
      // The line below uses PHP's dirname() function to go up 2 directory levels from the current file
      //
      // WHAT THIS MEANS:
      // If this bootstrap.php file is located at: /var/www/html/my-website/public/staff-directory/includes/bootstrap.php
      // Then:
      // __DIR__ = /var/www/html/my-website/public/staff-directory/includes
      // dirname(__DIR__, 1) = /var/www/html/my-website/public/staff-directory
      // dirname(__DIR__, 2) = /var/www/html/my-website/public
      // dirname(__DIR__, 3) = /var/www/html/my-website
      //
      // You need to adjust the number (2) to match your specific directory structure
      // For a typical subdirectory setup, you should use dirname(__DIR__, 3) to go up 3 levels
      define('BASE_PATH', dirname(__DIR__, 3)); // Project root

      // PRIVATE_PATH is where non-public files are stored
      define('PRIVATE_PATH', BASE_PATH); // Usually same as BASE_PATH

      // PUBLIC_PATH must point to the public directory containing your staff-directory folder
      // UNCOMMENT THE CORRECT OPTION BASED ON YOUR SETUP:

      // OPTION 1: If staff-directory is directly in your public folder
      // For example: /var/www/html/my-website/public/staff-directory/
      define('PUBLIC_PATH', BASE_PATH . '/public/staff-directory');

      // OPTION 2: If you moved all files directly to your public folder (no subdirectory)
      // For example: /var/www/html/my-website/public/
      // define('PUBLIC_PATH', BASE_PATH . '/public');

      // APP_BASE_URI is the URL path to your application - this is what appears in the browser address bar
      // This is VERY IMPORTANT for all links and assets to work correctly
      // UNCOMMENT THE CORRECT OPTION BASED ON YOUR SETUP:

      // OPTION 1: If your app is at https://example.com/staff-directory/
      // This is the most common setup - your application is in a subdirectory
      define('APP_BASE_URI', '/staff-directory'); // Include the leading slash

      // OPTION 2: If your app is at the root (https://example.com/ or https://staffdirectory.example.com/)
      // This is when your application is at the root of the domain
      // define('APP_BASE_URI', ''); // Empty string for root deployment
  }
  ```

  > **IMPORTANT:** The correct configuration depends on your specific server setup. The most common setup is to have the staff directory in a subdirectory, which would use OPTION 1 for both settings.

#### Understanding the Path Configuration

**Directory Structure Examples:**

1. **Subdirectory Deployment** (most common):
   ```
   /var/www/html/                                <- Server root
     └── my-website/                            <- Your website
         ├── public/                            <- Publicly accessible files
         │   └── staff-directory/               <- The staff directory application
         │       ├── includes/                  <- Application includes
         │       │   └── bootstrap.php          <- This file you're editing
         │       ├── admin/                     <- Admin area
         │       ├── assets/                    <- CSS, JS, images
         │       └── index.php                  <- Front page
         ├── vendor/                            <- Composer dependencies
         └── config/                            <- Configuration files
   ```

   In this case:
   - `BASE_PATH = /var/www/html/my-website`
   - `PUBLIC_PATH = /var/www/html/my-website/public/staff-directory`
   - `APP_BASE_URI = '/staff-directory'`
   - In bootstrap.php: `define('BASE_PATH', dirname(__DIR__, 3));` (go up 3 levels from includes)

2. **Root Deployment**:
   ```
   /var/www/html/                                <- Server root
     └── my-staff-directory/                     <- Your application
         ├── public/                            <- Publicly accessible files
         │   ├── includes/                      <- Application includes
         │   │   └── bootstrap.php              <- This file you're editing
         │   ├── admin/                         <- Admin area
         │   ├── assets/                        <- CSS, JS, images
         │   └── index.php                      <- Front page
         ├── vendor/                            <- Composer dependencies
         └── config/                            <- Configuration files
   ```

   In this case:
   - `BASE_PATH = /var/www/html/my-staff-directory`
   - `PUBLIC_PATH = /var/www/html/my-staff-directory/public`
   - `APP_BASE_URI = ''` (empty string)
   - In bootstrap.php: `define('BASE_PATH', dirname(__DIR__, 2));` (go up 2 levels from includes)

> **Note:** The paths above are examples. Your actual paths will depend on your server configuration.

### 2.2 Configure the Web Server

#### For Apache:

- [ ] Create or update the `.htaccess` file in your subdirectory with the following content:
  ```apache
  # Force HTTPS (optional but recommended)
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

  # Route all requests through the front controller
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^(.*)$ front-controller.php [L,QSA]

  # Set 404 error document - make sure to include the full path with subdirectory
  ErrorDocument 404 /staff-directory/front-controller.php

  # Protect against directory listing
  Options -Indexes
  ```

  > **Important:** Replace `/staff-directory/` in the ErrorDocument line with your actual subdirectory path.

#### For Nginx:

- [ ] Add the following to your server block configuration:
  ```nginx
  location /staff-directory/ {
      try_files $uri $uri/ /staff-directory/front-controller.php?$args;
  }

  # Handle PHP files
  location ~ \.php$ {
      include snippets/fastcgi-php.conf;
      fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # Adjust to your PHP version
  }
  ```

## Step 3: Verify Application Code Configuration

### 3.1 Check Router Configuration

- [ ] Open the `includes/Router.php` file and verify that the `dispatch` method correctly handles the subdirectory prefix:
  ```php
  public function dispatch($method, $uri) {
      // This line removes the subdirectory prefix from the URI
      $uri = str_replace(APP_BASE_URI, '', $uri);
      // ... rest of the method
  }
  ```

  > **What this does:** When someone visits `https://example.com/staff-directory/admin`, the Router removes the `/staff-directory` part so it can match routes correctly.

### 3.2 Ensure Proper Path Handling

- [ ] Check that all templates use the `url()` helper function for internal links:
  ```php
  <!-- CORRECT: This will generate the proper URL with the subdirectory -->
  <a href="<?php echo url('admin/index.php'); ?>">Admin</a>

  <!-- INCORRECT: This won't include the subdirectory -->
  <a href="/admin/index.php">Admin</a>
  ```

- [ ] Verify that all asset references use the `asset()` helper function:
  ```php
  <!-- CORRECT: This will include the subdirectory in the path -->
  <link href="<?php echo asset('css/styles.css'); ?>" rel="stylesheet">

  <!-- INCORRECT: This won't work in a subdirectory -->
  <link href="/css/styles.css" rel="stylesheet">
  ```

- [ ] Make sure all filesystem operations use `PUBLIC_PATH` instead of relative paths:
  ```php
  // CORRECT: This works regardless of subdirectory
  $file_path = PUBLIC_PATH . '/uploads/' . $filename;

  // INCORRECT: This might break in a subdirectory
  $file_path = __DIR__ . '/../uploads/' . $filename;
  ```

- [ ] Check that JavaScript has access to the base URI:
  ```php
  <script>
      // This makes the subdirectory path available to JavaScript
      window.APP_BASE_URI = "<?php echo APP_BASE_URI; ?>";
  </script>
  ```

- [ ] Ensure all JavaScript AJAX requests and dynamic URLs use `window.APP_BASE_URI`:
  ```javascript
  // CORRECT: This will work in a subdirectory
  fetch(`${window.APP_BASE_URI}/includes/ajax_handlers.php`)

  // INCORRECT: This won't include the subdirectory
  fetch('/includes/ajax_handlers.php')
  ```

### 3.3 Database Path Storage

- [ ] Verify that paths stored in the database don't include the subdirectory prefix:
  ```php
  // CORRECT: Store relative paths without APP_BASE_URI
  $path_to_store = '/uploads/companies/' . $filename;

  // INCORRECT: Don't store the full URL with subdirectory
  $path_to_store = url('uploads/companies/' . $filename);
  ```

  > **Why this matters:** When you display these paths later, you'll add the subdirectory prefix. If it's already in the database, you'll get double prefixes like `/staff-directory/staff-directory/uploads/...`

## Step 4: Test Your Subdirectory Deployment

### 4.1 Basic Functionality Testing

- [ ] Visit your application in the subdirectory (e.g., `https://example.com/staff-directory/`)
- [ ] Verify the homepage loads correctly with all styles and images
- [ ] Test navigation to ensure all links work properly
- [ ] Log in to the admin area to confirm authentication works

### 4.2 Advanced Testing

- [ ] Test form submissions to ensure they post to the correct URLs
- [ ] Upload images and verify they appear correctly
- [ ] Test all AJAX functionality (search, filtering, etc.)
- [ ] Try accessing a non-existent URL to verify 404 handling
- [ ] Check that all JavaScript features work correctly

### 4.3 Common Issues to Watch For

#### Double Prefixing

If you see URLs like `/staff-directory/staff-directory/...`:

- [ ] Check if paths stored in the database already include the subdirectory prefix
- [ ] Look for instances where the `url()` function is called on paths that already include the prefix
- [ ] Verify that upload functions aren't adding the subdirectory to paths before storing them

#### 404 Errors

If you encounter 404 errors:

- [ ] Verify the `.htaccess` file is correctly configured with the proper subdirectory path
- [ ] Check that the Router is properly removing the APP_BASE_URI prefix from URIs
- [ ] Ensure all links in templates use the `url()` helper function
- [ ] Check server error logs for more specific information

#### File Not Found Errors

If uploaded files can't be found:

- [ ] Verify that filesystem paths use `PUBLIC_PATH` instead of relative paths
- [ ] Check that database-stored paths are consistent (with or without leading slash)
- [ ] Ensure the upload directories exist and have proper permissions
- [ ] Verify that image URLs in HTML include the subdirectory prefix

## Step 5: Final Deployment Checklist

- [ ] Environment configuration is updated with correct subdirectory path
- [ ] Web server (.htaccess or Nginx config) is properly configured
- [ ] All code paths use the appropriate helper functions
- [ ] Database paths are stored correctly without the subdirectory prefix
- [ ] All functionality has been thoroughly tested
- [ ] Application caches have been cleared
- [ ] Error logs have been checked for any routing-related issues

## Need Help?

If you encounter issues with your subdirectory deployment:

1. Check the Apache/Nginx error logs for specific error messages
2. Verify that all paths in your code follow the guidelines in this document
3. Try accessing the application with and without trailing slashes to identify any routing issues
4. Use browser developer tools to identify any 404 errors in resource loading

Remember that most subdirectory deployment issues are related to path handling. Ensuring consistent use of the helper functions (`url()`, `asset()`) and constants (`PUBLIC_PATH`) will solve most problems.
