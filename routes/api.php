<?php

use Illuminate\Http\Request;
use App\Helpers\ProductImportHelper;

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

// User Groups
Route::group(['prefix' => 'group'], function() {
    Route::get('/all', 'GroupController@all');
    Route::get('/commissions', 'GroupController@allWithCommission');
    Route::get('/locations', 'GroupController@allWithLocation');
    Route::get('/{id}', 'GroupController@getById');
    Route::get('/{id}/users', 'GroupController@getUsersById');
    Route::get('/', 'GroupController@getByName');
    Route::post('/', 'GroupController@add');
    Route::put('/{id}', 'GroupController@update');
});

// Users
Route::group(['prefix' => 'user'], function() {
    Route::get('/{id}', 'UserController@getUser');
    Route::get('/{id}/affiliate/{affiliateId}', 'UserController@linkToAffiliate');
    Route::get('/{id}/affiliate', 'UserController@affiliate');
    Route::post('/', 'UserController@addUser');
    Route::put('/{id}', 'UserController@updateUser');
    Route::put('/{id}/account', 'UserController@createThirdpartyAccountForUser');
    Route::put('/{id}/permissions', 'UserController@updateUserPermissions');
    Route::put('/{id}/email', 'UserController@updateUserEmailAddress');
    Route::put('/{id}/reset/password', 'UserController@resetUserPassword');
    Route::delete('/{id}', 'UserController@deactivateUser');
});

Route::group(['prefix' => 'users'], function() {
    Route::get('/', 'UserController@listUsers');
    Route::get('/duplicates', 'UserController@listCognitoUsersWithDuplicateInstances'); // Reporting task to find all problematic Cognito User accounts
    Route::get('/uppercased', 'UserController@listCognitoUsersWithUppercasedEmails'); // Reporting task to find all problematic Cognito User accounts
});

// Locations
Route::group(['prefix' => 'location'], function() {
    Route::get('/{id}', 'LocationController@getById');
    Route::get('/{id}/users', 'LocationController@getUsersById');
    Route::get('/{id}/group', 'LocationController@getUserGroupById');
    Route::put('/{id}/reactivate', 'LocationController@reactivateLocation');
    Route::delete('/{id}', 'LocationController@deactivateLocation');
});

Route::get('/locations', 'LocationController@all');

// Cities
Route::get('/cities', 'CityController@all');
Route::get('/city/{id}', 'CityController@getById');

// Markets
Route::get('/markets', 'MarketController@all');
Route::get('/market/{id}', 'MarketController@getById');
Route::get('/market/{id}/subscriptions/counts', 'MarketController@getSubscriptionCount');

// Regions
Route::get('/regions', 'RegionController@all');
Route::get('/region/{id}', 'RegionController@getById');
Route::get('/region/{id}/subscriptions/counts', 'RegionController@getSubscriptionCount');

// Countries
Route::get('/countries', 'CountryController@all');
Route::get('/country/{id}', 'CountryController@getById');

// Permissions
Route::group(['prefix' => 'permissions'], function() {
    Route::get('/', 'PermissionsController@all');
});

// Emails
Route::post('/contact', 'TransactionalEmailController@save');

});
