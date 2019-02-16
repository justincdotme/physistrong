<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Exercise extends JsonResource
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
            'type' => 'exercise',
            'id' => (string)$this->id,
            'attributes' => [
                'name' => $this->name
            ],
            'links' => [
                'self' => route('exercises.show', ['exercise' => $this->id])
            ]
        ];
    }
}
