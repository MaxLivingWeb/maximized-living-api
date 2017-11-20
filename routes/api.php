<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'group'], function() {
    Route::get('/all', 'GroupController@all');
    Route::get('/{id}', 'GroupController@getById');
    Route::get('/', 'GroupController@getByName');
    Route::post('/', 'GroupController@add');
    Route::put('/{id}', 'GroupController@update');
    Route::delete('/{id}', 'GroupController@delete');
});

Route::group(['prefix' => 'user'], function() {
    Route::get('/{id}', 'UserController@getUser');
    Route::post('/', 'UserController@addUser');
    Route::put('/{id}', 'UserController@updateUser');
});

Route::group(['prefix' => 'commission'], function() {
    Route::get('/', 'CommissionController@all');
    Route::get('/{id}', 'CommissionController@getById');
    Route::post('/', 'CommissionController@add');
    Route::put('/{id}', 'CommissionController@update');
    Route::delete('/{id}', 'CommissionController@delete');
});

Route::group(['prefix' => 'permissions'], function() {
    Route::get('/', 'PermissionsController@all');
});

