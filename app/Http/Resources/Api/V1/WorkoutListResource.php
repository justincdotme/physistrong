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
            'exercises' => $this->whenLoaded('exercises', fn () => ExerciseSummaryResource::collection($this->exercises)),
            'entries_count' => $this->entries_count ?? 0,
            'completed_entries_count' => $this->completed_entries_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
