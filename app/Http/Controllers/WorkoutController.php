<?php

namespace App\Http\Controllers;

use App\Core\Workout;
use Illuminate\Http\Request;
use App\Http\Resources\Workout as WorkoutResource;

class WorkoutController extends Controller
{
    /**
     * WorkoutController constructor.
     */
    public function __construct()
    {
        $this->authorizeResource(Workout::class, 'workout');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return WorkoutResource::collection(
            auth()
                ->user()
                ->workouts()
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return WorkoutResource
     */
    public function store(Request $request)
    {
        $workout = Workout::create([
            'user_id' => auth()->user()->id
        ]);

        return new WorkoutResource($workout);
    }

    /**
     * Display the specified resource.
     *
     * @param Workout $workout
     * @return WorkoutResource
     */
    public function show(Workout $workout)
    {
        return new WorkoutResource($workout);
    }
}
