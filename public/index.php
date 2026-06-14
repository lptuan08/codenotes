<?php

// Entry point for all HTTP requests.
// The public web server should be configured to serve this file only.

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

$router = new Router();
require_once __DIR__ . '/../config/routes.php';


$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
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

    $response = [
        'error' => 'Internal Server Error',
    ];

    if ($isDebug) {
        $response['message'] = $e->getMessage();
        $response['file'] = $e->getFile();
        $response['line'] = $e->getLine();
        $response['trace'] = $e->getTrace();
    }
    echo json_encode($response);
}
