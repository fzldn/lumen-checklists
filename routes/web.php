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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Checklists
$router->get('/checklists', 'ChecklistController@index');
$router->post('/checklists', 'ChecklistController@store');
$router->get('/checklists/{checklistId}', 'ChecklistController@show');
$router->patch('/checklists/{checklistId}', 'ChecklistController@update');
$router->delete('/checklists/{checklistId}', 'ChecklistController@destroy');

// Items
