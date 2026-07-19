<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Concerns;

use App\Enums\ExerciseType;
use App\Models\EquipmentType;
use Illuminate\Validation\Rule;

trait ValidatesExerciseAttributes
{
    /** @return array<int, mixed> */
    protected function equipmentTypeRule(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('equipment_types', 'id')->where(EquipmentType::visibilityConstraint($this->user())),
        ];
    }

    /**
     * @param ExerciseType|null $type
     *
     * @return array<string, mixed>
     */
    protected function typeRulesFor(?ExerciseType $type): array
    {
        return match ($type) {
            ExerciseType::Resistance => [
                'type_attributes.bodyweight_base'     => ['boolean'],
                'type_attributes.allows_added_weight' => ['boolean'],
                'type_attributes.bilateral'           => ['boolean'],
            ],
            ExerciseType::TimedHold => [
                'type_attributes.target_duration_seconds' => ['nullable', 'integer', 'min:1'],
            ],
            ExerciseType::Distance => [
                'type_attributes.tracks_elevation' => ['boolean'],
            ],
            ExerciseType::Interval => [
                'type_attributes.default_work_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rest_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rounds'       => ['nullable', 'integer', 'min:1'],
            ],
            null => [],
        };
    }
}
