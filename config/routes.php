<?php

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





$router->add('GET', '/users', 'UserController@index');
$router->add('POST', '/users', 'UserController@store');
$router->add('GET', '/users/(\d+)', 'UserController@show');
