<?php
/**
 * Router
 *
 * Handles routing using FastRoute.
 */

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class Router {
    // Configuration is now handled directly in the constructor

    /**
     * @var \FastRoute\Dispatcher The FastRoute dispatcher
     */
    private $dispatcher;

    /**
     * @var bool Whether to redirect all unmatched routes to index.php
     */
    private $redirectUnmatchedToIndex = true;

    /**
     * Constructor
     *
     * @param array $config Configuration for the router
     */
    public function __construct(array $config = []) {
        // Extract configuration directly without storing the whole config
        $this->redirectUnmatchedToIndex = $config['routing']['redirectUnmatchedToIndex'] ?? true;
        $this->initializeDispatcher();
    }

    /**
     * Initialize the FastRoute dispatcher
     *
     * @return void
     */
    private function initializeDispatcher() {
        // Create a new dispatcher (no caching)
        $this->dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $r) {
            // Define routes
            $this->defineRoutes($r);
        });
    }

    /**
     * Define routes for the application
     *
     * @param RouteCollector $r The route collector
     * @return void
     */
    private function defineRoutes(RouteCollector $r) {
        // Home page
        $r->addRoute('GET', '/', 'HomeController@index');

        // Admin routes
        $r->addRoute('GET', '/admin', 'AdminController@index');
        $r->addRoute('GET', '/admin/index.php', 'AdminController@index');
        $r->addRoute('GET', '/admin/add.php', 'AdminController@add');
        $r->addRoute('POST', '/admin/add.php', 'AdminController@store');
        $r->addRoute('GET', '/admin/edit.php', 'AdminController@edit');
        $r->addRoute('POST', '/admin/edit.php', 'AdminController@update');
        $r->addRoute('GET', '/admin/departments.php', 'AdminController@departments');
        $r->addRoute('GET', '/admin/companies.php', 'AdminController@companies');
        $r->addRoute('GET', '/admin/settings.php', 'AdminController@settings');
        $r->addRoute('POST', '/admin/settings.php', 'AdminController@updateSettings');

        // Auth routes
        $r->addRoute('GET', '/admin/auth/check_login.php', 'AuthController@checkLogin');
        $r->addRoute('POST', '/admin/auth/login.php', 'AuthController@login');
        $r->addRoute('GET', '/admin/auth/logout.php', 'AuthController@logout');

        // AJAX routes
        $r->addRoute('GET', '/includes/ajax_handlers.php', 'AjaxController@handle');
        $r->addRoute('POST', '/includes/check_duplicate.php', 'AjaxController@checkDuplicate');

        // Image generation
        $r->addRoute('GET', '/includes/generate_placeholder.php', 'ImageController@generatePlaceholder');
    }

    /**
     * Dispatch the request
     *
     * @param string $httpMethod The HTTP method
     * @param string $uri The URI to dispatch
     * @return void
     */
    public function dispatch(string $httpMethod, string $uri) {
        // Logging is handled by the Monolog logger

        global $logger;
        $logger->debug("Dispatching request", ["method" => $httpMethod, "uri" => $uri]);
        // Strip query string and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // Remove the base URI prefix if present
        if (APP_BASE_URI !== '/' && strpos($uri, APP_BASE_URI) === 0) {
            $uri = substr($uri, strlen(APP_BASE_URI));
        }

        // Ensure URI starts with /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // Dispatch the request
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        global $logger;
        $logger->debug("Route info", $routeInfo);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // Handle 404 responses
                if ($this->redirectUnmatchedToIndex) {
                    // Use dedicated 404 handler that redirects to index.php
                    require PUBLIC_PATH . '/includes/404_handler.php';
                } else {
                    // If we don't want to redirect, show a 404 error
                    http_response_code(404);
                    echo '404 Not Found';
                }
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Method not allowed - show a 405 error
                http_response_code(405);
                $allowedMethods = $routeInfo[1];
                header('Allow: ' . implode(', ', $allowedMethods));
                echo '405 Method Not Allowed';
                break;

            case Dispatcher::FOUND:
                try {
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];

                    $this->handleRoute($handler, $vars);
                } catch (\Throwable $e) {
                    // Log the error
                    global $logger;
                    if (isset($logger)) {
                        $logger->error('Router error: ' . $e->getMessage(), [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }

                    // Show a 500 error
                    http_response_code(500);
                    echo '500 Internal Server Error';
                }
                break;
        }
    }

    /**
     * Handle a route
     *
     * @param mixed $handler The route handler
     * @param array $vars The route variables
     * @return void
     */
    private function handleRoute($handler, array $vars) {
        global $logger;
        $logger->debug("Handling route", ["handler" => $handler, "vars" => $vars]);

        // Handle controller@method string
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $method) = explode('@', $handler);
            $logger->debug("Controller and method", ["controller" => $controllerName, "method" => $method]);

            // Load all controller classes
            $this->loadControllers();
            $logger->debug("Controllers loaded");

            // Instantiate the controller
            $controller = new $controllerName();
            $logger->debug("Controller instantiated: " . get_class($controller));

            // Call the method on the controller
            if (method_exists($controller, $method)) {
                $logger->debug("Calling method on controller", ["controller" => get_class($controller), "method" => $method]);
                call_user_func_array([$controller, $method], $vars);
                $logger->debug("Method called successfully");
            } else {
                $logger->error("Method not found", ["controller" => get_class($controller), "method" => $method]);
                throw new \Exception("Method {$method} not found in controller {$controllerName}");
            }
        }
        // Handle callable
        elseif (is_callable($handler)) {
            call_user_func_array($handler, $vars);
        }
        // Handle unknown handler
        else {
            throw new \Exception('Invalid route handler');
        }
    }

    /**
     * Load all controller classes
     *
     * @return void
     */
    private function loadControllers() {
        // Define the controllers directory
        $controllersDir = PUBLIC_PATH . '/includes/controllers/';

        // Load each controller file
        require_once $controllersDir . 'HomeController.php';
        require_once $controllersDir . 'AdminController.php';
        require_once $controllersDir . 'AuthController.php';
        require_once $controllersDir . 'AjaxController.php';
        require_once $controllersDir . 'ImageController.php';
    }
}
