<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSetResource extends JsonResource
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
            'relationships' => new WorkoutSetRelationshipResource($this),
            'links' => [
                'self' => route('workouts.sets.show', ['workout' => $this->workout->id, 'set' => $this->id])
            ]
        ];
    }
}
