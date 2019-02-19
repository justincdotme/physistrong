<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseSet extends JsonResource
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
            'type' => 'set',
            'id' => (string)$this->id,
            'attributes' => [
                'exercise_id' => $this->exercise_id,
                'set_order' => $this->set_order,
                'count' => (string)$this->count,
                'weight' => (string)$this->weight
            ],
            'relationships' => new ExerciseSetRelationship($this),
            'links' => [
                'self' => route('workouts.exercises.sets.show', [
                    'workout' => $this->workout->id,
                    'exercise' => $this->exercise_id,
                    'set' => $this->id
                ])
            ]
        ];
    }
}