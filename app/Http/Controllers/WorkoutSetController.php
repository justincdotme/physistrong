<?php

namespace App\Http\Controllers;

use App\Core\Set;
use App\Core\Workout;
use App\Http\Resources\ExerciseSet;

class WorkoutSetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Workout::class, 'workout');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Workout $workout
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Workout $workout)
    {
        return ExerciseSet::collection($workout->sets()->get());
    }
}
