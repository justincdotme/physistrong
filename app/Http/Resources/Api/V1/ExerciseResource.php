<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Exercise;
use App\Repositories\ExerciseRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Exercise */
class ExerciseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'equipment_type_id' => $this->equipment_type_id,
            'equipment_type' => $this->whenLoaded('equipmentType', fn () => new EquipmentTypeResource($this->equipmentType)),
            'type_attributes' => app(ExerciseRepository::class)->typeAttributes($this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'usage_count' => (int) ($this->getAttribute('workouts_count') ?? 0)
                + (int) ($this->getAttribute('templates_count') ?? 0),
            'has_logged_data' => (bool) ($this->getAttribute('has_logged_data') ?? false),
        ];
    }
}
