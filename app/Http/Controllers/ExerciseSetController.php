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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
}
