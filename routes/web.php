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

// Templates
$router->get('/checklists/templates', 'TemplateController@index');
$router->post('/checklists/templates', 'TemplateController@store');
$router->get('/checklists/templates/{templateId}', 'TemplateController@show');
$router->patch('/checklists/templates/{templateId}', 'TemplateController@update');
$router->delete('/checklists/templates/{templateId}', 'TemplateController@destroy');
$router->post('/checklists/templates/{templateId}/assigns', 'TemplateController@assigns');

// Items
$router->get('/checklists/{checklistId}/items', 'ItemController@index');
$router->post('/checklists/{checklistId}/items', 'ItemController@store');
$router->get('/checklists/{checklistId}/items/{itemId}', 'ItemController@show');
$router->patch('/checklists/{checklistId}/items/{itemId}', 'ItemController@update');
$router->delete('/checklists/{checklistId}/items/{itemId}', 'ItemController@destroy');
$router->post('/checklists/{checklistId}/items/_bulk', 'ItemController@updateBulk');
$router->get('/checklists/items/summaries', 'ItemController@summary');
$router->post('/checklists/complete', 'ItemController@complete');
$router->post('/checklists/incomplete', 'ItemController@incomplete');

// Checklists
$router->get('/checklists', 'ChecklistController@index');
$router->post('/checklists', 'ChecklistController@store');
$router->get('/checklists/{checklistId}', 'ChecklistController@show');
$router->patch('/checklists/{checklistId}', 'ChecklistController@update');
$router->delete('/checklists/{checklistId}', 'ChecklistController@destroy');
