<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\EntryGroupController;
use App\Http\Controllers\Api\V1\EquipmentTypeController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\TemplateCloneController;
use App\Http\Controllers\Api\V1\TemplateEntryGroupController;
use App\Http\Controllers\Api\V1\TemplateExerciseController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkoutController;
use App\Http\Controllers\Api\V1\WorkoutEntryController;
use App\Http\Controllers\Api\V1\WorkoutExerciseController;
use App\Http\Controllers\Api\V1\WorkoutTemplateController;
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
    Route::apiResource('workouts', WorkoutController::class);
    Route::post('workouts/{workout}/exercises', [WorkoutExerciseController::class, 'attach']);
    Route::delete('workouts/{workout}/exercises/{exercise}', [WorkoutExerciseController::class, 'detach']);
    Route::put('workouts/{workout}/exercises/reorder', [WorkoutExerciseController::class, 'reorder']);
    Route::put('workouts/{workout}/entries/reorder', [WorkoutEntryController::class, 'reorder']);
    Route::apiResource('workouts.entries', WorkoutEntryController::class)->scoped();
    Route::apiResource('templates', WorkoutTemplateController::class);
    Route::post('templates/{template}/exercises', [TemplateExerciseController::class, 'attach']);
    Route::delete('templates/{template}/exercises/{exercise}', [TemplateExerciseController::class, 'detach']);
    Route::put('templates/{template}/exercises/reorder', [TemplateExerciseController::class, 'reorder']);
    Route::post('templates/{template}/clone', TemplateCloneController::class);
    Route::post('templates/{template}/groups', [TemplateEntryGroupController::class, 'store']);
    Route::delete('templates/{template}/groups/{group}', [TemplateEntryGroupController::class, 'destroy']);
    Route::post('templates/{template}/groups/{group}/exercises', [TemplateEntryGroupController::class, 'assignExercises']);
    Route::delete('templates/{template}/groups/{group}/exercises/{exercise}', [TemplateEntryGroupController::class, 'removeExercise']);
    Route::post('workouts/{workout}/groups', [EntryGroupController::class, 'store']);
    Route::put('workouts/{workout}/groups/{group}', [EntryGroupController::class, 'update']);
    Route::delete('workouts/{workout}/groups/{group}', [EntryGroupController::class, 'destroy']);
    Route::post('workouts/{workout}/groups/{group}/entries', [EntryGroupController::class, 'assignEntries']);
    Route::delete('workouts/{workout}/groups/{group}/entries/{entry}', [EntryGroupController::class, 'removeEntry']);
});
