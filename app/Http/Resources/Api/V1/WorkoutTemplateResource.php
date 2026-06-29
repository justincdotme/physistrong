<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkoutTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkoutTemplate */
class WorkoutTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'exercises' => $this->whenLoaded('exercises', function (): array {
                return $this->exercises->map(fn ($exercise) => [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'type' => $exercise->type,
                    'equipment_type_id' => $exercise->equipment_type_id,
                    'exercise_order' => $exercise->pivot->exercise_order,
                    'template_entry_group_id' => $exercise->pivot->template_entry_group_id,
                ])->all();
            }),
            'groups' => $this->whenLoaded('groups', function (): array {
                return $this->groups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'planned_rounds' => $group->planned_rounds,
                    'rest_between_exercises_seconds' => $group->rest_between_exercises_seconds,
                    'rest_between_rounds_seconds' => $group->rest_between_rounds_seconds,
                ])->all();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
