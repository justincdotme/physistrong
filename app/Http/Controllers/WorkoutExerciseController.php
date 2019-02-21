<?php

namespace App\Http\Controllers;

use App\Core\Workout;
use App\Core\Exercise;
use App\Exceptions\UndeletableException;
use App\Http\Resources\Workout as WorkoutResource;
use App\Http\Resources\Exercise as ExerciseResource;

class WorkoutExerciseController extends Controller
{
    /**
     * Display a list of a workout's exercises.
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
     * Add an exercise to a workout.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
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

        $workout->addExercise($exercise, request('exercise_order'));

        return (new WorkoutResource($workout))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove an exercise from a workout.
     * Provided no sets of the exercise exist.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws UndeletableException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Workout $workout, Exercise $exercise)
    {
        $this->authorize('delete', [Workout::class, $workout]);

        if ($workout->sets()->ofExercise($exercise)->count()) {
            throw new UndeletableException();
        }

        $workout->exercises()->detach($exercise->id);

        return response(null, 204);
    }
}
