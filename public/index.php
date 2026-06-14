<?php

// Entry point for all HTTP requests.
// The public web server should be configured to serve this file only.

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;

$router = new Router();
require_once __DIR__ . '/../config/routes.php';

try {
    // Dispatch the current HTTP request to the router.
    // parse_url() extracts only the path portion of the requested URL.
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'],
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
} catch (Throwable $e) {
    // Fallback global error handler for unexpected exceptions.
    http_response_code(500);

    echo json_encode([
        'error' => 'Internal Server Error'
    ]);
}
