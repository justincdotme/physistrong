<?php

namespace App\Http\Controllers;

use App\Core\Exercise;
use Illuminate\Http\Request;
use App\Rules\UniqueExercise;
use App\Http\Resources\Exercise as ExerciseResource;

class ExerciseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Exercise::class);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Exercise $exercise
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Exercise $exercise)
    {
        $user = auth()->user();
        $this->validate(
            request(),
            [
                'name' => [
                    'required', new UniqueExercise($exercise, $user)
                ],
                'exercise_type' => 'required|integer|exists:exercise_types,id'
            ]
        );

        $exercise = $exercise->create([
            'name' => request('name'),
            'exercise_type_id' => request('exercise_type'),
            'user_id' =>  $user->id
        ]);

        return (new ExerciseResource($exercise))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param Exercise $exercise
     * @return ExerciseResource
     */
    public function show(Exercise $exercise)
    {
        return new ExerciseResource($exercise);
    }

    /**
     * @param Exercise $exercise
     * @return ExerciseResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Exercise $exercise)
    {
        $this->validate(
            request(),
            [
                'name' => [
                    'required', new UniqueExercise($exercise, auth()->user())
                ]
            ]
        );
        $exercise->update(
            request()->only('name')
        );

        return new ExerciseResource($exercise);
    }
}
