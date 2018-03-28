<?php

use Illuminate\Http\Request;
use App\Helpers\ShopifyHelper;
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
    Route::put('/{id}/reactivate', 'GroupController@reactivateUserGroup');
    Route::delete('/{id}', 'GroupController@deactivateUserGroup');
});

// Users
Route::group(['prefix' => 'user'], function() {
    Route::get('/{id}', 'UserController@getUser');
    Route::post('/', 'UserController@addUser');
    Route::put('/{id}', 'UserController@updateUser');
    Route::put('/{id}/account', 'UserController@createThirdpartyAccountForUser');
    Route::put('/{id}/email', 'UserController@updateUserEmailAddress');
    Route::put('/{id}/shopify', 'UserController@updateUserShopifyID');
    Route::get('/{id}/affiliate/{affiliateId}', 'UserController@linkToAffiliate');
    Route::get('/{id}/affiliate', 'UserController@affiliate');
    Route::delete('/{id}', 'UserController@deactivateUser');
});

Route::group(['prefix' => 'users'], function() {
    Route::get('/', 'UserController@listUsers');
    Route::get('/duplicates', 'UserController@listCognitoUsersWithDuplicateInstances'); // Reporting task to find all problematic Cognito User accounts
    Route::get('/uppercased', 'UserController@listCognitoUsersWithUppercasedEmails'); // Reporting task to find all problematic Cognito User accounts
    Route::get('/group_by/{groupName}', 'UserController@listUsers');
});

// Locations
Route::group(['prefix' => 'location'], function() {
    Route::get('/{id}/users', 'LocationController@getUsersById');
    Route::get('/{id}/group', 'LocationController@getUserGroupById');
});

Route::get('/locations', 'LocationController@all');

// Cities
Route::get('/cities', 'CityController@all');
Route::get('/city/{id}', 'CityController@getById');

// Regions
Route::get('/regions', 'RegionController@all');
Route::get('/region/{id}', 'RegionController@getById');
Route::get('/region/{id}/subscriptions/counts', 'RegionController@getSubscriptionCount');

// Countries
Route::get('/countries', 'CountryController@all');
Route::get('/country/{id}', 'CountryController@getById');

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
    // All Sales
    Route::get('/sales', 'Reporting\SalesController@sales');

    // Retail Sales
    Route::group(['prefix' => 'retail'], function() {
        Route::group(['prefix' => 'customer'], function() {
            Route::get('/sales', 'Reporting\RetailController@customerSales');
        });
        Route::group(['prefix' => 'pos'], function() {
            Route::get('/sales', 'Reporting\RetailController@posSales');
        });
    });

    // Affiliate Sales
    Route::group(['prefix' => 'affiliate'], function() {
        Route::get('/sales', 'Reporting\AffiliateController@sales');
        Route::get('/{id}/sales', 'Reporting\AffiliateController@salesById');
    });

    // Wholesale Sales
    Route::group(['prefix' => 'wholesale'], function() {
        Route::get('/sales', 'Reporting\WholesaleController@sales');
        Route::get('{id}/sales', 'Reporting\WholesaleController@salesById');
    });
});

// Store
Route::group(['prefix' => 'store'], function() {
    // Update ML Store Products
    Route::get('/update-products', 'Shopify\ProductController@importProductsToDatabase');

    // Search for Products
    Route::get('/search', 'SearchController@index');

    // Get ALL Product Audience Types
    Route::group(['prefix' => 'products'], function() {
        Route::get('/', 'Shopify\ProductController@getProducts');
        Route::get('/audience_types', 'Shopify\ProductController@getAllProductsAudienceTypes');
    });
});

//Google My Business
Route::group(['prefix' => 'gmb'], function() {
    Route::get('/get_all', 'GmbController@get_all');
    Route::get('/get/{gmb_id}', 'GmbController@get');
});
