# Roadmap: Subdirectory Deployment & Routing Integration

This document outlines the steps required to restructure the Staff Directory application for deployment within a subdirectory (e.g., `/staff-directory/`) on a web server, potentially alongside an existing WordPress installation, and integrate `nikic/fast-route` for internal PHP routing.

**Goal:** Run the application from `https://www.mynewproject.com/staff-directory/`, storing sensitive files outside the web root, while ensuring correct routing and path management.

## PHP 7.4 Compatibility Notes

- Ensure all dependencies specify PHP 7.4 compatible versions in composer.json
- Use typed properties where appropriate (PHP 7.4 feature)
- Avoid arrow functions (=>), use traditional closures instead
- Use null coalescing operator (??) for null checks
- Utilize array spread operator for array manipulation
- Remember that union types are NOT available (PHP 8.0 feature)

## Recommended composer.json constraints:

```json
{
    "require": {
        "php": "^7.4",
        "nikic/fast-route": "^1.3",
        "php-di/php-di": "^6.4",
        "monolog/monolog": "^2.9"
    }
}
```

## Phase 1: Preparation & Restructuring

-   [x] **Dependency Analysis:**
    - [x] Audit existing `composer.json` for routing-related dependencies
    - [x] Check for conflicts with `nikic/fast-route`
    - [x] Consider adding complementary packages:
      ```bash
      composer require nikic/fast-route
      composer require php-di/php-di # For proper dependency injection
      composer require monolog/monolog # For improved logging
      ```
-   [x] **Backup Project:** Create a full backup of the current project state.
-   [x] **Verify local test environment (e.g., https://staffdirectory.local) to test changes before deployment.**
-   [x] **Define Server Structure:** Understand the deployment flexibility while maintaining the current private files structure:

    ```
    project-root/           <-- Private files remain unchanged
        config/
        staff_dir_env/
        vendor/
        src/
        composer.json
        composer.lock
        ...

    # Deployment Scenarios for Public Files:

    # Scenario A: Direct in web root
    /var/www/html/         <-- Web root (public_html, www, etc.)
        .htaccess
        index.php
        assets/
        uploads/
        ...

    # Scenario B: In subdirectory
    /var/www/html/         <-- Web root
        staff-directory/   <-- Application public files
            .htaccess
            index.php
            assets/
            uploads/
            ...

    # Scenario C: In nested subdirectories
    /var/www/html/         <-- Web root
        apps/
            staff-directory/  <-- Application public files
                .htaccess
                index.php
                assets/
                uploads/
                ...
    ```

    **Note:** Only the public-facing files (currently in `public/`) need to be deployable to different locations. The private files structure remains unchanged regardless of the deployment scenario.

-   [x] **Move Files (Locally First):** Simulate the server structure locally.
    -   **Note:** In this project, the *project root* is already private (outside the web root) both locally and on the deployment server. Sensitive files and directories like `config/`, `staff_dir_env/`, `vendor/`, `composer.json`, `composer.lock`, and `src/` should remain in the project root.
    -   Create a new `staff-directory` folder inside the existing `public/` (or `public_html/`) folder, which is the web root.
    -   Move all public-facing contents *except* `staff-directory` from `public/` into `public/staff-directory/`. The `public/` folder itself might become redundant or just contain the `staff-directory` folder. *Alternatively, rename `public` to `staff-directory` and adjust paths accordingly.*

## Phase 2: Bootstrap & Configuration Update (`includes/bootstrap.php`)

-   [ ] **Update Bootstrap Configuration:** Modify `includes/bootstrap.php` as the central configuration point:
    ```php
    <?php
    // Example: Assuming private_files is one level above public_html,
    // and staff-directory is inside public_html.
    define('BASE_PATH', dirname(__DIR__, 2)); // Adjust depth as needed
    define('PRIVATE_PATH', BASE_PATH . '/private_files'); // Or the chosen name
    define('PUBLIC_PATH', dirname(__DIR__)); // Path to the public folder
    define('APP_BASE_URI', '/staff-directory'); // The base URI for routing

    // Error reporting, sessions, etc.
    // ...

    // Include Autoloader
    require PRIVATE_PATH . '/vendor/autoload.php';

    // Load Environment Variables
    require PRIVATE_PATH . '/config/env_loader.php';

    // Load Configuration
    $dbConfig = require PRIVATE_PATH . '/config/database.php';
    $authConfig = require PRIVATE_PATH . '/config/auth_config.php';
    ```

-   [ ] **Update Include Paths:** Review and update all includes in:
    - `includes/header.php`
    - `includes/admin_header.php`
    - `includes/footer.php`
    - `includes/admin_footer.php`
    to use the new path constants

-   [ ] **Asset Path Management:**
    - Create helper function in `bootstrap.php` for asset URLs:
    ```php
    function asset_url($path) {
        return APP_BASE_URI . '/assets/' . ltrim($path, '/');
    }
    ```
    - Update all asset references in headers and footers to use this function

-   [ ] **Update Authentication Paths:**
    - Modify `admin/auth/auth.php` to use new constants
    - Update login/logout redirect paths
    - Ensure session handling uses correct paths

-   [ ] **AJAX Endpoints:**
    - Update `includes/ajax_handlers.php` paths
    - Modify any JavaScript files that make AJAX calls to use `APP_BASE_URI`
    - Update `check_duplicate.php` endpoint path

-   [ ] **Image Processing:**
    - Update upload paths in image handling code
    - Modify image URL generation to include `APP_BASE_URI`
    - Ensure `uploads` directory path is correctly referenced

This revised Phase 2 better reflects your current architecture where `bootstrap.php` is the central configuration point, included by the header files. The focus shifts from `index.php` to ensuring all includes and paths are properly configured in `bootstrap.php` and the files that include it.

## Phase 2.5: Configuration Consolidation

-   [ ] **Create Unified Config:**
    ```php
    // config/app.php
    return [
        'paths' => [
            'base' => BASE_PATH,
            'private' => PRIVATE_PATH,
            'public' => PUBLIC_PATH,
            'uploads' => PUBLIC_PATH . '/uploads',
        ],
        'urls' => [
            'base' => APP_BASE_URI,
            'assets' => APP_BASE_URI . '/assets',
        ],
        'routing' => [
            'cache' => true,
            'cacheFile' => PRIVATE_PATH . '/cache/routes.cache',
        ]
    ];
    ```

-   [ ] **Implement Asset Manager:** Create a class to handle asset paths and versioning.
    ```php
    class AssetManager {
        private static ?array $manifest = null;
        private string $manifestPath;
        private string $publicPath;

        public function __construct(string $publicPath) {
            $this->publicPath = $publicPath;
            $this->manifestPath = $publicPath . '/assets/manifest.json';
        }

        public function asset(string $path): string {
            if (self::$manifest === null) {
                self::$manifest = $this->loadManifest();
            }

            $assetPath = self::$manifest[$path] ?? $path;
            return $this->baseUrl('/assets/' . $assetPath);
        }

        private function loadManifest(): array {
            if (file_exists($this->manifestPath)) {
                return json_decode(file_get_contents($this->manifestPath), true) ?? [];
            }
            return [];
        }

        private function baseUrl(string $path): string {
            return APP_BASE_URI . $path;
        }
    }

    // Usage in bootstrap:
    $assetManager = new AssetManager(PUBLIC_PATH);
    ```

-   [ ] **Create Asset Helper Function:** Add a global helper function for templates.
    ```php
    // Add to helpers section
    function asset($path) {
        $manifestPath = PUBLIC_PATH . '/assets/manifest.json';
        static $manifest = null;

        if ($manifest === null && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        $assetPath = isset($manifest[$path]) ? $manifest[$path] : $path;
        return baseUrl('/assets/' . $assetPath);
    }

    // Usage in templates
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    ```

## Phase 3: Routing Implementation (`staff-directory/index.php`)

-   [ ] **Implement Middleware Stack:** Create a middleware system to handle request processing.
    ```php
    class MiddlewareStack {
        private $middlewares = [];

        public function add(callable $middleware) {
            $this->middlewares[] = $middleware;
        }

        public function handle($request, $next) {
            foreach ($this->middlewares as $middleware) {
                $next = fn($req) => $middleware($req, $next);
            }
            return $next($request);
        }
    }
    ```

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

    class ErrorHandler {
        public function handle404(array $context): void {
            http_response_code(404);
            // Handle 404 error
        }

        public function handle405(array $context): void {
            http_response_code(405);
            header('Allow: ' . implode(', ', $context['allowedMethods']));
            // Handle 405 error
        }

        public function handle500(\Throwable $error): void {
            http_response_code(500);
            // Handle 500 error
        }
    }

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            $errorHandler = new ErrorHandler();
            $errorHandler->handle404([
                'requestUri' => $uri,
                'baseUri' => APP_BASE_URI
            ]);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            $errorHandler = new ErrorHandler();
            $errorHandler->handle405([
                'allowedMethods' => $allowedMethods,
                'requestMethod' => $httpMethod
            ]);
            break;
        case FastRoute\Dispatcher::FOUND:
            try {
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
            } catch (Exception $e) {
                $errorHandler = new ErrorHandler();
                $errorHandler->handle500($e);
            }
            break;
    }

    ?>
    ```
-   [ ] **Refactor Handlers:** Adapt existing PHP scripts (`admin/add.php`, `includes/ajax_handlers.php`, etc.) to work with the router. Ideally, move logic into controller classes/methods or functions that the router can call. Avoid direct script includes as handlers if possible.
-   [ ] **Update Forms & Links:** Ensure HTML forms submit to the correct routed paths (e.g., `/staff-directory/admin/add` instead of `/staff-directory/admin/add.php`) and links use the new routes.

## Phase 3.5: Build Process & Asset Pipeline Adaptation


## Phase 4: Testing & Initial Deployment
- [ ] Test all routes
- [ ] Verify authentication flows
- [ ] Check all redirects
- [ ] Validate API endpoints
- [ ] Deploy and test in production (still in root directory)

## Phase 5: Subdirectory Structure Implementation
- [ ] Move files to subdirectory structure
- [ ] Update .htaccess rules
- [ ] Adjust base paths and URLs
- [ ] Test deployment in subdirectory

## Phase 6: Build Process Adaptation
-   [ ] **Create Build Configuration System:**
    ```bash
    npm install dotenv --save-dev
    ```
    - Create `build.config.js` to centralize path management
    - Ensure it reads from existing `staff_dir_env/.env`
    - Maintain compatibility with current dev server setup

-   [ ] **Update Package Scripts:**
    - Modify existing `dev` script to use build config while preserving concurrent processes:
      - PHP built-in server (keep `-t public` for development)
      - SASS watching (maintain current paths)
      - Tailwind watching (update output path only)
      - BrowserSync (update proxy path)
    - Update `build` script for production deployments
    - Keep all processes running under `concurrently`

-   [ ] **Development Environment Compatibility:**
    - Ensure PHP server still serves from `public/` in development
    - Configure BrowserSync to handle subdirectory path
    - Maintain live reload functionality for PHP and CSS files
    - Keep current watch patterns (`public/**/*.php, public/assets/css/styles.css`)

-   [ ] **Production Build Process:**
    - Create production build script using `build.config.js`
    - Automate subdirectory asset compilation
    - Maintain current directory structure:
      ```
      public/
        assets/
          fonts/
            Outfit/
            remixicon/
          css/
            styles.css
      ```
    - Support moving entire `public/` contents to subdirectory

-   [ ] **Testing & Verification:**
    - Verify development server works as before
    - Test production builds in subdirectory
    - Ensure all concurrent processes function correctly
    - Validate BrowserSync proxy with subdirectory

This phase now properly accounts for your existing development setup while adding the flexibility to deploy to subdirectories.


This organization allows us to:
1. Validate the routing changes independently
2. Ensure core functionality works before touching the build system
3. Handle one major change at a time
4. Have working fallback points if issues arise


