<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return file_get_contents(resource_path('views/body.html'));
});

$router->get('/{type:[f,l,F,L]}/{identity}', 'ResourcesController@get');

$router->group(['prefix' => 'api/'], function () use ($router) {
    $router->post('login', 'LoginController@login');
    $router->post('registration', 'RegisterController@register');

    $router->post('resources/files', 'ResourcesController@files');
    $router->post('resources/links', 'ResourcesController@links');

    $router->get('resources', 'ResourcesController@index');
    $router->get('resources/{id}', 'ResourcesController@show');
});

$router->group(['prefix' => 'api/', 'middleware' => 'auth'], function () use ($router) {
    $router->get('profile', 'ProfileController@show');
    $router->put('profile', 'ProfileController@update');

    $router->delete('logout', 'LoginController@logout');

    $router->put('resources/{id}', 'ResourcesController@update');
    $router->delete('resources/{id}', 'ResourcesController@delete');
});