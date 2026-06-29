<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\ExerciseType;
use App\Models\Exercise;
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
            'type_attributes' => $this->typeAttributes(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'usage_count' => $this->getAttribute('usage_count') ?? 0,
        ];
    }

    /** @return array<string, mixed>|null */
    private function typeAttributes(): ?array
    {
        $child = match ($this->type) {
            ExerciseType::Resistance => $this->resistance,
            ExerciseType::TimedHold => $this->timedHold,
            ExerciseType::Distance => $this->distance,
            ExerciseType::Interval => $this->interval,
        };

        if ($child === null) {
            return null;
        }

        return collect($child->toArray())
            ->except(['id', 'exercise_id', 'created_at', 'updated_at'])
            ->all();
    }
}
