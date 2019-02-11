<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutRelationshipResource extends JsonResource
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
                'data' => new UserResource($this->user)
            ],
            'sets' => [
                'links' => [
                    'self' => route('workouts.sets.index', ['workout' => $this->id])
                ],
                'data' => new UserResource($this->user)
            ],
        ];
    }
}
