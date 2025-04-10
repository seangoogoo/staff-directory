# Roadmap: Subdirectory Deployment & Routing Integration

This document outlines the steps required to restructure the Staff Directory application for deployment within a subdirectory (e.g., `/staff-directory/`) on a web server, potentially alongside an existing WordPress installation, and integrate `nikic/fast-route` for internal PHP routing.

**Goal:** Run the application from `https://www.mynewproject.com/staff-directory/`, storing sensitive files outside the web root, while ensuring correct routing and path management.

## Phase 1: Preparation & Restructuring

-   [ ] **Backup Project:** Create a full backup of the current project state.
-   [ ] **Verify local test environment (e.g., https://staffdirectory.local) to test changes before deployment.**
-   [ ] **Define Server Structure:** Confirm the final directory structure on the target server. Example:


    ```
    ../private_files/  <-- Outside web root
        config/
        staff_dir_env/
        vendor/
        src/          <-- If applicable
        composer.json
        composer.lock
        ...           <-- Other non-public files
    public_html/       <-- Web root (e.g., where WordPress lives)
        .htaccess     <-- WordPress root .htaccess (usually untouched)
        wp-config.php <-- WordPress files...
        ...
        staff-directory/  <-- Your application's public-facing files
            .htaccess     <-- App-specific rewrite rules
            index.php     <-- App entry point
            assets/       <-- CSS, JS, Images
            uploads/      <-- If uploads are public
            ...           <-- Other files originally in local `public/`
    ```

-   [ ] **Install Router:** Add `nikic/fast-route` to the project dependencies.

    ```bash
    composer require nikic/fast-route
    ```

-   [ ] **Move Files (Locally First):** Simulate the server structure locally.
    -   **Note:** In this project, the *project root* is already private (outside the web root) both locally and on the deployment server. Sensitive files and directories like `config/`, `staff_dir_env/`, `vendor/`, `composer.json`, `composer.lock`, and `src/` should remain in the project root.
    -   Create a new `staff-directory` folder inside the existing `public/` (or `public_html/`) folder, which is the web root.
    -   Move all public-facing contents *except* `staff-directory` from `public/` into `public/staff-directory/`. The `public/` folder itself might become redundant or just contain the `staff-directory` folder. *Alternatively, rename `public` to `staff-directory` and adjust paths accordingly.*

## Phase 2: Bootstrap & Configuration Update (`staff-directory/index.php`)

-   [ ] **Update `index.php` Location:** Ensure your main entry point is now `staff-directory/index.php` (or the renamed `public/index.php`).
-   [ ] **Define Base Paths:** In `staff-directory/index.php`, establish reliable paths to the `private_files` directory and the application's public root (`staff-directory`).
    ```php
    <?php
    // Example: Assuming private_files is one level above public_html,
    // and staff-directory is inside public_html.
    define('BASE_PATH', dirname(__DIR__, 2)); // Adjust depth as needed
    define('PRIVATE_PATH', BASE_PATH . '/private_files'); // Or the chosen name
    define('PUBLIC_PATH', __DIR__); // Path to the current 'staff-directory' folder
    define('APP_BASE_URI', '/staff-directory'); // The base URI for routing

    // Error reporting, sessions, etc.
    // ...

    // Include Autoloader
    require PRIVATE_PATH . '/vendor/autoload.php';

    // Load Environment Variables (adjust path)
    // Example using a loader potentially in config/
    require PRIVATE_PATH . '/config/env_loader.php'; // Ensure env_loader uses PRIVATE_PATH

    // Load Configuration (adjust paths)
    $dbConfig = require PRIVATE_PATH . '/config/database.php';
    $authConfig = require PRIVATE_PATH . '/config/auth_config.php';
    // ... ensure all config loads use PRIVATE_PATH

    // Dependency Injection Container (if used)
    // ... setup container, potentially passing paths

    ?>
    ```
-   [ ] **Update Path Constants:** Review all code (especially `config/`, `includes/`) for hardcoded paths or constants like `__DIR__` that might break. Ensure they correctly reference files relative to the new structure or use the defined `PRIVATE_PATH` and `PUBLIC_PATH`. Check `env_loader.php`, `database.php`, `auth_config.php`, `functions.php`, etc.
-   [ ] **Verify Asset Paths:** Ensure CSS, JS, and image links in templates/HTML correctly point to `/staff-directory/assets/...` or use a base URL variable.

## Phase 3: Routing Implementation (`staff-directory/index.php`)

-   [ ] **Integrate FastRoute:** Add routing logic to `staff-directory/index.php` after bootstrapping.
    ```php
    <?php
    // ... (Previous bootstrap code: paths, autoload, config) ...

    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
        // Define application routes relative to APP_BASE_URI
        $r->addRoute('GET', '/', 'App\Controllers\HomeController@index'); // Example
        $r->addRoute('GET', '/users', 'App\Controllers\UserController@list');
        $r->addRoute('GET', '/user/{id:\d+}', 'App\Controllers\UserController@show');

        // Admin routes
        $r->addRoute('GET', '/admin', 'App\Controllers\Admin\DashboardController@index');
        $r->addRoute('GET', '/admin/settings', 'App\Controllers\Admin\SettingsController@show');
        // ... add all GET, POST, etc. routes

        // Example: Map old script paths if needed (less ideal)
        // $r->addRoute(['GET', 'POST'], '/admin/add.php', function() { require PUBLIC_PATH . '/admin/add.php'; });

    });

    // Fetch method and URI
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    // **Crucial:** Remove the base URI prefix if present
    if (APP_BASE_URI !== '/' && strpos($uri, APP_BASE_URI) === 0) {
        $uri = substr($uri, strlen(APP_BASE_URI));
    }
    // Ensure URI starts with / for matching
    if (empty($uri)) {
        $uri = '/';
    }

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            // ... 404 Not Found
            http_response_code(404);
            echo '404 Not Found'; // Replace with proper error page
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // ... 405 Method Not Allowed
            http_response_code(405);
            echo '405 Method Not Allowed'; // Replace with proper error page
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            // --- Execute the handler ---
            // This part needs adaptation based on your app structure
            // Option 1: Controller@method string (requires parsing and instantiation)
            if (is_string($handler) && strpos($handler, '@') !== false) {
                list($class, $method) = explode('@', $handler);
                // Need logic to instantiate $class (maybe from a container)
                // $controller = new $class(/* dependencies */);
                // call_user_func_array([$controller, $method], $vars);
                echo "Dispatching to controller {$class}@{$method} with vars: " . print_r($vars, true); // Placeholder
            }
            // Option 2: Closure/Function
            elseif (is_callable($handler)) {
                 call_user_func_array($handler, $vars);
            }
            // Option 3: Include script (less ideal for routing)
            // elseif (is_string($handler) && file_exists(PUBLIC_PATH . $handler)) {
            //     // Pass $vars somehow if needed (e.g., global scope, request object)
            //     require PUBLIC_PATH . $handler;
            // }
            else {
                 // Handle error: Invalid handler
                 http_response_code(500);
                 echo '500 Internal Server Error - Invalid Route Handler';
            }
            break;
    }

    ?>
    ```
-   [ ] **Refactor Handlers:** Adapt existing PHP scripts (`admin/add.php`, `includes/ajax_handlers.php`, etc.) to work with the router. Ideally, move logic into controller classes/methods or functions that the router can call. Avoid direct script includes as handlers if possible.
-   [ ] **Update Forms & Links:** Ensure HTML forms submit to the correct routed paths (e.g., `/staff-directory/admin/add` instead of `/staff-directory/admin/add.php`) and links use the new routes.

## Phase 4: Server Configuration (`staff-directory/.htaccess`)

-   [ ] **Create `.htaccess`:** Place the following `.htaccess` file inside the `staff-directory/` folder.
    ```apache
    <IfModule mod_rewrite.c>
      RewriteEngine On

      # Set the base path for rewrite rules
      RewriteBase /staff-directory/

      # Redirect Trailing Slashes If Not A Folder...
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule ^(.*)/$ /$1 [L,R=301]

      # Handle Front Controller...
      # Do not rewrite if the request is for a valid file or directory
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-f
      # Rewrite all other requests to index.php
      RewriteRule ^ index.php [L]
    </IfModule>

    # Optional: Improve security (prevent directory listing, etc.)
    <IfModule mod_autoindex.c>
      Options -Indexes
    </IfModule>
    ```
-   [ ] **Test `.htaccess`:** Verify that requests like `https://www.mynewproject.com/staff-directory/some/route` are correctly routed to `staff-directory/index.php` and not resulting in 404s from Apache itself (before PHP handles it). Check server error logs if issues arise.

## Phase 5: Deployment & Testing

-   [ ] **Upload Files:** Upload the restructured files via FTP (or preferred method) to the target server, placing `staff-directory/` in the web root and `private_files/` outside the web root.
-   [ ] **Set Permissions:** Ensure the web server has the necessary read permissions for `private_files/` and write permissions for any required directories (e.g., `uploads/`, `logs/`).
-   [ ] **Test Thoroughly:**
    -   Access `https://www.mynewproject.com/staff-directory/`.
    -   Test all application routes (user-facing and admin).
    -   Test form submissions.
    -   Verify asset loading (CSS, JS, images).
    -   Check functionality requiring access to `config/` or `staff_dir_env/`.
    -   Ensure no conflicts with the root WordPress site.
-   [ ] **Review Logs:** Check PHP error logs and Apache logs on the server for any issues.
