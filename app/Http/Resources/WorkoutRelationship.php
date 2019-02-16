<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutRelationship extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'user' => [
                'links' => [
                    'self' => route('users.show', ['user' => $this->user->id])
                ],
                'data' => new User($this->user)
            ],
            'sets' => [
                'links' => [
                    'self' => route('workouts.sets.index', ['workout' => $this->id])
                ]
            ],
            'exercises' => [
                'links' => [
                    'self' => route('workouts.exercises.index', ['workout' => $this->id])
                ]
            ],
        ];
    }
}
