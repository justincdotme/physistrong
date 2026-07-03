<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Requests\Api\V1\Concerns\ValidatesEntryMetrics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutEntryRequest extends FormRequest
{
    use ValidatesEntryMetrics;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'set_order' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'entry_group_id' => [
                'sometimes',
                'nullable',
                Rule::exists('entry_groups', 'id')->where('workout_id', $this->route('workout')->id),
            ],
            'group_round' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'metrics' => ['sometimes', 'array'],
        ], $this->metricRules());
    }

    protected function metricExerciseType(): ?ExerciseType
    {
        return $this->route('entry')->exercise->type;
    }
}
