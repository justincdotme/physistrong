<?php

namespace App\Http\Controllers;

use App\Core\Workout;
use App\Core\Exercise;
use App\Http\Resources\Workout as WorkoutResource;
use App\Http\Resources\Exercise as ExerciseResource;

class WorkoutExerciseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Workout $workout
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Workout $workout)
    {
        $this->authorize('view', [Workout::class, $workout]);
        return ExerciseResource::collection($workout->exercises);
    }

    /**
     * @param Workout $workout
     * @param Exercise $exercise
     * @return
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Workout $workout, Exercise $exercise)
    {
        $this->authorize('update', [Workout::class, $workout]);
        $this->validate(
            request(),
            [
                'exercise_order' => 'required|integer'
            ]
        );

        $workout->exercises()->save($exercise, ['exercise_order' => request('exercise_order')]);

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }
}
