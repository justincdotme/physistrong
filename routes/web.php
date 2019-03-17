<?php

Route::group(['middleware' => 'guest'], function () {
    Route::post('user/login', 'UserController@login')->name('user.login');
});

Route::group(['middleware' => 'jwt.verify'], function () {
    Route::post('user/logout', 'UserController@logout')->name('user.logout');
});


Route::get('/{any?}', 'AppController@index')->name('app.home');

