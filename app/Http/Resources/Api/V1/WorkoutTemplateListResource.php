<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkoutTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkoutTemplate */
class WorkoutTemplateListResource extends JsonResource
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
                ])->all();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
