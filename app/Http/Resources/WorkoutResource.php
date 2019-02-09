<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
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
                'date' => $this->created_at->toDateTimeString(),
            ],
            'relationships' => new WorkoutRelationshipResource($this),
            'links' => [
                'self' => route('workouts.show', ['workout' => $this->id])
            ]
        ];
    }
}
