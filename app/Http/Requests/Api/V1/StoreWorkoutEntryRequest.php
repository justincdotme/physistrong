<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\DistanceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutEntryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'exercise_id' => [
                'required',
                Rule::exists('exercises', 'id')->where(function ($query) {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', $this->user()->id);
                }),
            ],
            'set_order' => ['required', 'integer', 'min:0'],
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

    /** @return array<string, mixed> */
    private function metricRules(): array
    {
        return [
            'metrics.load' => ['sometimes', 'array'],
            'metrics.load.target_weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metrics.load.actual_weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metrics.load.bodyweight_only' => ['sometimes', 'boolean'],

            'metrics.reps' => ['sometimes', 'array'],
            'metrics.reps.target_reps' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.reps.actual_reps' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.reps.to_failure' => ['sometimes', 'boolean'],
            'metrics.reps.failure_rep' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'metrics.duration' => ['sometimes', 'array'],
            'metrics.duration.target_duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.duration.actual_duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'metrics.distance' => ['sometimes', 'array'],
            'metrics.distance.target_distance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metrics.distance.actual_distance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metrics.distance.distance_unit' => ['sometimes', Rule::enum(DistanceUnit::class)],
            'metrics.distance.lap_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.distance.stroke_count' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'metrics.cardio_settings' => ['sometimes', 'array'],
            'metrics.cardio_settings.resistance_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.cardio_settings.incline' => ['sometimes', 'nullable', 'numeric'],
            'metrics.cardio_settings.speed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metrics.cardio_settings.cadence' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'metrics.interval_header' => ['sometimes', 'array'],
            'metrics.interval_header.programmed_rounds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'metrics.interval_header.completed_rounds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.target_work_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.target_rest_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.rounds' => ['sometimes', 'array'],
            'metrics.interval_header.rounds.*.round_number' => ['required_with:metrics.interval_header.rounds', 'integer', 'min:1'],
            'metrics.interval_header.rounds.*.actual_work_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.rounds.*.actual_rest_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.rounds.*.heart_rate_avg' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.interval_header.rounds.*.heart_rate_peak' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'metrics.intensity' => ['sometimes', 'array'],
            'metrics.intensity.rpe' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'metrics.intensity.avg_hr' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metrics.intensity.max_hr' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
