<?php

namespace App\Http\Controllers;

use App\Core\Set;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Http\Request;
use App\Http\Resources\ExerciseSet;

class ExerciseSetController extends Controller
{
    /**
     * ExerciseSetController constructor.
     */
    public function __construct()
    {
        $this->authorizeResource(Workout::class, 'workout');
    }

    /**
     * Show a list of exercise sets for a workout.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Workout $workout, Exercise $exercise)
    {
        return ExerciseSet::collection(
            $workout->sets()->ofExercise($exercise)->get()
        );
    }

    /**
     * Show an exercise set.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @param Set $set
     * @return ExerciseSet
     */
    public function show(Workout $workout, Exercise $exercise, Set $set)
    {
        return new ExerciseSet($set);
    }

    /**
     * Add a new exercise set.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Workout $workout, Exercise $exercise, Request $request)
    {
        $this->authorize('create', [Set::class, $workout]);
        $this->validate(
            request(),
            [
                'weight' => 'integer',
                'count' => 'integer|min:1',
                'set_order' => 'required|integer|min:1'
            ]
        );

        $set = Set::forWorkout(
            $workout,
            $exercise,
            request('set_order'),
            request('weight'),
            request('count')
        );

        return (new ExerciseSet($set))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update exercise set.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @param Set $set
     * @return ExerciseSet
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Workout $workout, Exercise $exercise, Set $set)
    {
        $this->validate(
            request(),
            [
                'weight' => 'integer',
                'count' => 'integer|min:1',
                'set_order' => 'required|integer|min:1'
            ]
        );

        $set->update(request()->only(['weight', 'count', 'set_order']));

        return (new ExerciseSet($set));
    }
}
