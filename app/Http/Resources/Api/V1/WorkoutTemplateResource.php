<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkoutTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkoutTemplate */
class WorkoutTemplateResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'notes'      => $this->notes,
            'exercises'  => $this->whenLoaded('exercises', fn () => ExerciseSummaryResource::collection($this->exercises)),
            'groups'     => $this->whenLoaded('groups', fn () => TemplateEntryGroupResource::collection($this->groups)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
