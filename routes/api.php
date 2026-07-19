<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\EntryGroupController;
use App\Http\Controllers\Api\V1\EquipmentTypeController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\ExerciseProgressController;
use App\Http\Controllers\Api\V1\TemplateEntryGroupController;
use App\Http\Controllers\Api\V1\TemplateExerciseController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkoutController;
use App\Http\Controllers\Api\V1\WorkoutCopyController;
use App\Http\Controllers\Api\V1\WorkoutEntryController;
use App\Http\Controllers\Api\V1\WorkoutExerciseController;
use App\Http\Controllers\Api\V1\WorkoutTemplateCloneController;
use App\Http\Controllers\Api\V1\WorkoutTemplateController;
use App\Http\Middleware\RejectBlacklistedTokens;
use Illuminate\Support\Facades\Route;

// Login rate-limits in the controller (incremented on failure, reset on success) to allow legitimate retries; register, password forgot and password reset use global middleware limits.
Route::post('/register', RegisterController::class)->middleware('throttle:auth');
Route::post('/login', LoginController::class);
Route::post('/password/forgot', ForgotPasswordController::class)->middleware('throttle:password-forgot');
Route::post('/password/reset', ResetPasswordController::class)->middleware('throttle:auth');

Route::middleware(['auth:api', RejectBlacklistedTokens::class])->group(function (): void {
    Route::post('/logout', LogoutController::class);
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::apiResource('equipment-types', EquipmentTypeController::class)->except(['show', 'update']);
    Route::apiResource('exercises', ExerciseController::class);
    Route::get('exercises/{exercise}/progress', [ExerciseProgressController::class, 'progress']);
    Route::get('exercises/{exercise}/records', [ExerciseProgressController::class, 'records']);
    Route::apiResource('workouts', WorkoutController::class);
    Route::post('workouts/{workout}/exercises', [WorkoutExerciseController::class, 'attach']);
    Route::delete('workouts/{workout}/exercises/{exercise}', [WorkoutExerciseController::class, 'detach']);
    Route::put('workouts/{workout}/exercises/reorder', [WorkoutExerciseController::class, 'reorder']);
    Route::put('workouts/{workout}/entries/reorder', [WorkoutEntryController::class, 'reorder']);
    Route::apiResource('workouts.entries', WorkoutEntryController::class)->scoped()->except(['index', 'show']);
    Route::apiResource('templates', WorkoutTemplateController::class);
    Route::post('templates/{template}/exercises', [TemplateExerciseController::class, 'attach']);
    Route::delete('templates/{template}/exercises/{exercise}', [TemplateExerciseController::class, 'detach']);
    Route::put('templates/{template}/exercises/reorder', [TemplateExerciseController::class, 'reorder']);
    Route::post('templates/{template}/clone', WorkoutTemplateCloneController::class);
    Route::post('workouts/{workout}/copy', WorkoutCopyController::class);
    Route::post('templates/{template}/groups', [TemplateEntryGroupController::class, 'store']);
    Route::post('workouts/{workout}/groups', [EntryGroupController::class, 'store']);
    Route::scopeBindings()->group(function (): void {
        Route::delete('templates/{template}/groups/{group}', [TemplateEntryGroupController::class, 'destroy']);
        Route::post('templates/{template}/groups/{group}/exercises', [TemplateEntryGroupController::class, 'assignExercises']);
        Route::delete('workouts/{workout}/groups/{group}', [EntryGroupController::class, 'destroy']);
        Route::post('workouts/{workout}/groups/{group}/entries', [EntryGroupController::class, 'assignEntries']);
    });
});
