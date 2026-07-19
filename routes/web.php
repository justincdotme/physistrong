<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', fn () => view('app'))->where('path', '.*');
