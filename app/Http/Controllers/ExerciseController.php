<?php

namespace App\Http\Controllers;

use App\Core\Exercise;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\Exercise as ExerciseResource;

class ExerciseController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $this->validate(
            request(),
            [
                'name' => [
                    'required',
                    Rule::unique('exercises')->where(function ($nameQuery) use ($user) {
                        return $nameQuery->where('name', request('name'))
                            ->where('user_id', $user->id);
                    })
                ],
                'exercise_type' => 'required|integer|exists:exercise_types,id'
            ]
        );

        $exercise = Exercise::create([
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
}
