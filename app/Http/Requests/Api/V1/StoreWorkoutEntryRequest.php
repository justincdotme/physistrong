<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Requests\Api\V1\Concerns\ValidatesEntryMetrics;
use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutEntryRequest extends FormRequest
{
    use ValidatesEntryMetrics;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'exercise_id' => [
                'required',
                Rule::exists('exercises', 'id')->where(Exercise::visibilityConstraint($this->user())),
            ],
            'set_order'      => ['required', 'integer', 'min:0'],
            'notes'          => ['sometimes', 'nullable', 'string'],
            'entry_group_id' => [
                'sometimes',
                'nullable',
                Rule::exists('entry_groups', 'id')->where('workout_id', $this->route('workout')->id),
            ],
            'group_round' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'metrics'     => ['sometimes', 'array'],
        ], $this->metricRules());
    }

    /** @return ExerciseType|null */
    protected function metricExerciseType(): ?ExerciseType
    {
        return Exercise::find($this->input('exercise_id'))?->type;
    }
}
