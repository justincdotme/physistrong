<?php

Route::get('/{any?}', function () {
    return view('templates.app');
});

