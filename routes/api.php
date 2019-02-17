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
        Route::resource('workouts', 'WorkoutController')->except(['destroy', 'edit', 'create']);
        Route::resource('users', 'UserController')->only(['show']);
        Route::post('workouts/{workout}/exercises/{exercise}', 'WorkoutExerciseController@store')
            ->name('workouts.exercises.store');
        Route::resource('workouts/{workout}/exercises', 'WorkoutExerciseController', [
            'as' => 'workouts'
        ])->only(['index', 'destroy']);
        Route::resource('workouts/{workout}/exercises/{exercise}/sets', 'ExerciseSetController', [
            'as' => 'workouts.exercises'
        ])->except(['create', 'edit']);
        Route::resource('workouts/{workout}/sets', 'WorkoutSetController', [
            'as' => 'workouts'
        ])->only(['index']);
        Route::resource('exercises', 'ExerciseController')->only(['show', 'store', 'update']);
    });
});
