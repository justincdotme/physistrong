<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\EquipmentTypeController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);
Route::post('/password/forgot', ForgotPasswordController::class);
Route::post('/password/reset', ResetPasswordController::class);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', LogoutController::class);
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::apiResource('equipment-types', EquipmentTypeController::class);
    Route::apiResource('exercises', ExerciseController::class);
});
