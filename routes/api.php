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
    Route::get('/all', function (Request $request) {
        return App\UserGroup::all();
    });

    Route::get('/{id}', function ($id) {
        return App\UserGroup::findOrFail($id);
    });

    Route::get('/', function (Request $request) {
        return App\UserGroup::where('group_name', $request->input('name'))->firstOrFail();
    });

    Route::post('/', function (Request $request) {
        App\UserGroup::create(['group_name' => $request->input('group_name'), 'discount_id' => intval($request->input('discount_id'))]);
    });

    Route::put('/{id}', function ($id, Request $request) {
        $group = App\UserGroup::findOrFail($id);

        $group->discount_id = intval($request->input('discount_id'));

        $group->save();
    });

    Route::delete('/{id}', function ($id, Request $request) {
        $group = App\UserGroup::findOrFail($id);

        $group->delete();
    });
});


