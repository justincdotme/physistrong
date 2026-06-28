<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workout */
class WorkoutListResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date->toDateString(),
            'exhaustion' => $this->exhaustion,
            'soreness' => $this->soreness,
            'exercises' => $this->whenLoaded('exercises', fn () => $this->exercises->map(fn ($exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'type' => $exercise->type,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
