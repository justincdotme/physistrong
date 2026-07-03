<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exercises')->where('user_id', $this->user()->id),
            ],
            'type' => ['required', Rule::enum(ExerciseType::class)],
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

        return array_merge($rules, $this->typeSpecificRules());
    }

    /** @return array<string, mixed> */
    private function typeSpecificRules(): array
    {
        return match ($this->input('type')) {
            'resistance' => [
                'type_attributes.bodyweight_base' => ['boolean'],
                'type_attributes.allows_added_weight' => ['boolean'],
                'type_attributes.bilateral' => ['boolean'],
            ],
            'timed_hold' => [
                'type_attributes.target_duration_seconds' => ['nullable', 'integer', 'min:1'],
            ],
            'distance' => [
                'type_attributes.tracks_elevation' => ['boolean'],
            ],
            'interval' => [
                'type_attributes.default_work_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rest_seconds' => ['nullable', 'integer', 'min:1'],
                'type_attributes.default_rounds' => ['nullable', 'integer', 'min:1'],
            ],
            default => [],
        };
    }
}
