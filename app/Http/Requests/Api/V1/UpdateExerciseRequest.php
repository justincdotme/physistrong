<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\DistanceUnit;
use App\Enums\ExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $exercise = $this->route('exercise');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exercises')
                    ->where('user_id', $this->user()->id)
                    ->ignore($exercise),
            ],
            'equipment_type_id' => [
                'nullable',
                'integer',
                Rule::exists('equipment_types', 'id')->where(function ($query) {
                    $query->where('is_system', true)
                        ->orWhere('user_id', $this->user()->id);
                }),
            ],
            'notes' => ['nullable', 'string'],
            'type_attributes' => ['sometimes', 'array'],
        ];

        return array_merge($rules, $this->typeSpecificRules($exercise->type));
    }

    /** @return array<string, mixed> */
    private function typeSpecificRules(ExerciseType $type): array
    {
        return match ($type) {
            ExerciseType::Resistance => [
                'type_attributes.bodyweight_base' => ['boolean'],
                'type_attributes.allows_added_weight' => ['boolean'],
                'type_attributes.bilateral' => ['boolean'],
            ],
            ExerciseType::TimedHold => [
                'type_attributes.target_duration_seconds' => ['nullable', 'integer', 'min:1'],
            ],
            ExerciseType::Distance => [
                'type_attributes.distance_unit' => [Rule::enum(DistanceUnit::class)],
                'type_attributes.tracks_elevation' => ['boolean'],
            ],
            ExerciseType::Interval => [
                'type_attributes.default_work_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rest_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rounds' => ['nullable', 'integer', 'min:1'],
            ],
        };
    }
}
