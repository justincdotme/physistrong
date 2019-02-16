<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Workout extends JsonResource
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
            'type' => 'workout',
            'id' => (string)$this->id,
            'attributes' => [
                'name' => $this->name
            ],
            'relationships' => new WorkoutRelationship($this),
            'links' => [
                'self' => route('workouts.show', ['workout' => $this->id])
            ]
        ];
    }
}
