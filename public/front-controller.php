<?php
/**
 * Front Controller
 *
 * This is the entry point for all requests to the application.
 * It preserves the behavior of redirecting unexisting requests to index.php.
 */

// Include bootstrap file
require_once __DIR__ . '/includes/bootstrap.php';

// Include router and middleware
require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/MiddlewareStack.php';

// Load application configuration
$config = load_app_config();

// Create router
$router = new Router($config);

// Create middleware stack
$middlewareStack = new MiddlewareStack();

// Add middleware (can be expanded later)
$middlewareStack->add(function($request, $next) {
    // Start output buffering
    ob_start();

    // Call the next middleware
    $response = $next($request);

    // End output buffering
    ob_end_flush();

    return $response;
});

// Logging is now handled by the Monolog logger in bootstrap.php

// Process the request through the middleware stack
$middlewareStack->handle($_SERVER, function($request) use ($router) {
    // Dispatch the request
    $router->dispatch($request['REQUEST_METHOD'], $request['REQUEST_URI']);

    return $request;
});
