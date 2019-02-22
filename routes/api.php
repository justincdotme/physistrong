<?php

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
        ])->only(['index', 'store']);
        Route::resource('sets', 'ExerciseSetController')->only(['show', 'update', 'destroy']);
        Route::resource('workouts/{workout}/sets', 'WorkoutSetController', [
            'as' => 'workouts'
        ])->only(['index']);
        Route::resource('exercises', 'ExerciseController')->only(['show', 'store', 'update']);
    });
    Route::group(['middleware' => 'guest'], function () {
        Route::post('user/register', 'UserController@store')->name('user.store');
        Route::post('user/login', 'UserController@login')->name('user.login');
        Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.request');
        Route::post('password/reset/{token}', 'Auth\ResetPasswordController@reset')->name('password.reset');
    });
});
