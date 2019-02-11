<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSetRelationshipResource extends JsonResource
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
            'workout' => [
                'links' => [
                    'self' => route('workouts.show', ['workout' => $this->workout->id])
                ],
                'data' => new WorkoutResource($this->workout)
            ],
            'exercise' => [
                'links' => [
                    'self' => route('exercises.show', ['exercise' => $this->exercise->id])
                ],
                'data' => new ExerciseResource($this->exercise)
            ],
        ];
    }
}
