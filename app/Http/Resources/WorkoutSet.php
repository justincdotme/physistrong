<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSet extends JsonResource
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
                'date' => $this->created_at->toDateTimeString(),
            ],
            'relationships' => new WorkoutSetRelationship($this),
            'links' => [
                'self' => route('workouts.sets.show', ['workout' => $this->workout->id, 'set' => $this->id])
            ]
        ];
    }
}
