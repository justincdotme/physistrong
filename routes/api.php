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

Route::group(['prefix' => 'v1'], function () {
    Route::group(['middleware' => 'jwt.verify'], function () {
        Route::resource('workouts', 'WorkoutController')->except(['create', 'edit']);
        Route::resource('users', 'UserController')->except(['create', 'store']);
    });
});