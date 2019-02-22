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
     * Show a list of exercise sets for a workout.
     *
     * @param Workout $workout
     * @param Exercise $exercise
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Workout $workout, Exercise $exercise)
    {
        $this->authorize('view', $workout);
        return ExerciseSet::collection(
            $workout->sets()->ofExercise($exercise)->get()
        );
    }

    /**
     * Show an exercise set.
     *
     * @param Set $set
     * @return ExerciseSet
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Set $set)
    {
        $this->authorize('view', $set->workout);
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
    public function store(Workout $workout, Exercise $exercise, Set $set)
    {
        $this->authorize('update', $workout);
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
     * @param Set $set
     * @return ExerciseSet
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Set $set)
    {
        $this->authorize('update', $set);
        $this->validate(
            request(),
            [
                'weight' => 'integer',
                'count' => 'integer|min:1',
                'set_order' => 'required|integer|min:1'
            ]
        );

        $set->update(request()->only(['weight', 'count', 'set_order']));

        return new ExerciseSet($set);
    }

    /**
     * Destroy an exercise set.
     *
     * @param Set $set
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Set $set)
    {
        $this->authorize('destroy', $set);
        $set->delete();

        return response(null, 204);
    }
}
