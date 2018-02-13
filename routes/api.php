<?php

use Illuminate\Http\Request;
use App\Helpers\ShopifyHelper;
use App\Helpers\ProductHelper;

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
    Route::delete('/{id}', 'GroupController@delete');
});

// Users
Route::group(['prefix' => 'user'], function() {
    Route::get('/{id}', 'UserController@getUser');
    Route::post('/', 'UserController@addUser');
    Route::put('/{id}', 'UserController@updateUser');
    Route::get('/{id}/affiliate/{affiliateId}', 'UserController@linkToAffiliate');
    Route::get('/{id}/affiliate', 'UserController@affiliate');
    Route::delete('/{id}', 'UserController@delete');
});

Route::group(['prefix' => 'users'], function() {
    Route::get('/', 'UserController@listUsers');
    Route::get('/all', 'UserController@listAllUsers');
    Route::get('/duplicates', 'UserController@listDuplicateUsers');
});

// Locations
Route::group(['prefix' => 'location'], function() {
    Route::get('/{id}/users', 'LocationController@getUsersById');
    Route::get('/{id}/group', 'LocationController@getUserGroupById');
});

Route::get('/locations', 'LocationController@all');

// Commissions
Route::group(['prefix' => 'commission'], function() {
    Route::get('/', 'CommissionController@all');
    Route::get('/{id}', 'CommissionController@getById');
    Route::post('/', 'CommissionController@add');
    Route::put('/{id}', 'CommissionController@update');
    Route::delete('/{id}', 'CommissionController@delete');
});

// Permissions
Route::group(['prefix' => 'permissions'], function() {
    Route::get('/', 'PermissionsController@all');
});

// Emails
Route::post('/contact', 'TransactionalEmailController@save');

// Reporting
Route::group(['prefix' => 'reporting'], function() {
    Route::get('/sales', 'Reporting\SalesController@sales');

    Route::group(['prefix' => 'retail'], function() {
        Route::get('/sales', 'Reporting\RetailController@sales');
    });

    Route::group(['prefix' => 'affiliate'], function() {
        Route::get('/sales', 'Reporting\AffiliateController@sales');
        Route::get('/{id}/sales', 'Reporting\AffiliateController@salesById');
    });

    Route::group(['prefix' => 'wholesale'], function() {
        Route::get('/sales', 'Reporting\WholesaleController@sales');
        Route::get('{id}/sales', 'Reporting\WholesaleController@salesById');
    });
});

Route::get('/store/update-products', function () {
    $products = (new ShopifyHelper())
        ->getProducts([], FALSE);
    
    (new ProductHelper())
        ->importProducts($products);
});

Route::get('/store/search', 'SearchController@index');
