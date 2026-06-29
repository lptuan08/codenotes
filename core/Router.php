<?php

namespace Core;

/**
 * Simple router implementation for matching HTTP requests.
 */
class Router
{
    private array $routes = [];

    /**
     * Register a new route.
     *
     * @param string $method  HTTP method (GET, POST, PUT, DELETE, ...)
     * @param string $path    Route path or regex pattern for path matching
     * @param string $handler Controller@action string
     * @param array $middleware Middleware class names to run before the controller
     */
    public function add(string $method, string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Match the incoming request and execute the corresponding controller action.
     */
    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            $pattern = '#^' . $route['path'] . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // Remove the full match, leaving only capture groups.
                array_shift($matches);

                [$controller, $action] = explode('@', $route['handler']);
                $controllerClass = 'App\\Controllers\\' . $controller;

                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Controller not found']);
                    return;
                }

                $controllerObject = new $controllerClass();

                foreach ($route['middleware'] as $middlewareClass) {
                    if (!class_exists($middlewareClass)) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Middleware not found']);
                        return;
                    }

                    $middleware = new $middlewareClass();

                    if (!method_exists($middleware, 'handle')) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Middleware handle method not found']);
                        return;
                    }

                    if ($middleware->handle() === false) {
                        return;
                    }
                }

                if (!method_exists($controllerObject, $action)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Action not found']);
                    return;
                }

                $controllerObject->$action(...$matches);
                return;
            }
        }

        // No route matched the request.
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}
