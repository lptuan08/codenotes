<?php

use App\Middleware\JwtMiddleware;

/**
 * Route definitions for the application.
 *
 * Each route is registered with the Router using:
 *   $router->add('METHOD', 'PATH', 'Controller@action');
 *
 * The last route uses a regex capture group to match an ID value.
 * In this simple router implementation, dynamic segments are defined
 * with regular expressions directly in the path.
 *
 * NOTE: Keep route order in mind when adding overlapping paths.
 */

/** @var \Core\Router $router */

//----------- AUTHENTICATION -------------

// Register
$router->add('POST', '/auth/register', 'AuthController@register');
// Verify email by code
$router->add('POST', '/auth/verify-email', 'AuthController@verifyEmail');
//login
$router->add('POST', '/auth/login', 'AuthController@login');


// JwtMiddleware::class -> App\Middleware\JwtMiddleware. PHP sẽ lấy alias đã import bằng use

$router->add('GET', '/users', 'UserController@index', ["App\\Middleware\\JwtMiddleware"]);
$router->add('POST', '/users', 'UserController@store', [JwtMiddleware::class]);
$router->add('GET', '/users/(\d+)', 'UserController@show', [JwtMiddleware::class]);
