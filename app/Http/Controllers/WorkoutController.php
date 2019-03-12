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
     * @param  \Illuminate\Http\Request $request
     * @return WorkoutResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $this->validate(
            request(),
            [
                'name' => 'required',
                'date_scheduled' => ['required',  'date']
            ]
        );

        $workout = Workout::create([
            'user_id' => auth()->user()->id,
            'name' => request('name'),
            'date_scheduled' => request('date_scheduled')
        ]);

        return new WorkoutResource($workout);
    }

    public function update(Workout $workout)
    {
        $this->validate(
            request(),
            [
                'name' => 'required',
                'date_scheduled' => ['required',  'date']
            ]
        );

        $workout->update([
            'name' => request('name'),
            'date_scheduled' => request('date_scheduled')
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
