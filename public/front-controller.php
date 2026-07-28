<?php
/**
 * Front Controller
 *
 * This is the entry point for all requests to the application.
 * It dispatches every request through the router; anything that matches no
 * route is answered with a real 404, rendered in place and never redirected.
 */

// Include bootstrap file
require_once __DIR__ . '/includes/bootstrap.php';

// Include router and middleware
require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/MiddlewareStack.php';

// Create router
$router = new Router();

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
