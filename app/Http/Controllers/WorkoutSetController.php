<?php

namespace App\Http\Controllers;

use App\Core\Set;
use App\Core\Workout;
use App\Core\Exercise;
use Illuminate\Http\Request;
use App\Http\Resources\WorkoutSet;
use App\Http\Resources\WorkoutSetCollection;

class WorkoutSetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Workout $workout
     * @return WorkoutSetCollection
     */
    public function index(Workout $workout)
    {
        return new WorkoutSetCollection($workout->getSets());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Workout $workout
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Workout $workout, Request $request)
    {
        $this->authorize('create', [Set::class, $workout]);
        $this->validate(
            request(),
            [
                'exercise' => 'required|integer',
                'weight' => 'required|integer',
                'count' => 'required|integer|min:1'
            ]
        );

        $set = Set::forWorkout(
            $workout,
            Exercise::findOrFail(request('exercise')),
            request('weight'),
            request('count')
        );

        return (new WorkoutSet($set))
            ->response()
            ->setStatusCode(201);
    }
}
