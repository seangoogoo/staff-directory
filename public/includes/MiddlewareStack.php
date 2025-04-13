<?php
/**
 * Middleware Stack
 * 
 * Handles request processing through a stack of middleware functions.
 */

class MiddlewareStack {
    /**
     * @var array List of middleware callables
     */
    private $middlewares = [];

    /**
     * Add a middleware to the stack
     * 
     * @param callable $middleware The middleware function
     * @return void
     */
    public function add(callable $middleware) {
        $this->middlewares[] = $middleware;
    }

    /**
     * Process the request through the middleware stack
     * 
     * @param mixed $request The request to process
     * @param callable $next The next function to call
     * @return mixed The processed request
     */
    public function handle($request, $next) {
        // Create a nested stack of middleware functions
        foreach ($this->middlewares as $middleware) {
            // Use traditional closure instead of arrow function for PHP 7.4 compatibility
            $next = function($req) use ($middleware, $next) {
                return $middleware($req, $next);
            };
        }
        
        // Execute the middleware stack
        return $next($request);
    }
}
