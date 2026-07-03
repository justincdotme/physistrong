<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Exercise */
class ExerciseSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'equipment_type_id' => $this->equipment_type_id,
        ];

        if ($this->pivot) {
            $data['exercise_order'] = $this->pivot->exercise_order;

            if ($this->pivot->getTable() === 'template_exercises') {
                $data['template_entry_group_id'] = $this->pivot->template_entry_group_id;
            }
        }

        return $data;
    }
}
