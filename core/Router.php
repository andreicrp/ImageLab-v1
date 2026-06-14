<?php

/**
 * ImageLab Router Class
 */
class Router {
    protected array $routes = [];

    /**
     * Map a route pattern to a handler callback
     * 
     * @param string $path URL path pattern (e.g., '/api/upload')
     * @param callable $handler Function or controller action to run
     */
    public function add(string $path, callable $handler): void {
        $this->routes[$path] = $handler;
    }

    /**
     * Dispatch the current request URI to registered handlers
     * 
     * @param string $uri The requested URI path
     */
    public function dispatch(string $uri): void {
        // Parse the path from the URL
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove trailing slash if present (except for root root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        if (array_key_exists($path, $this->routes)) {
            call_user_func($this->routes[$path]);
            return;
        }

        // Return a basic 404 response
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested route '{$path}' is not registered in the Router skeleton.</p>";
    }
}
