<?php

declare(strict_types=1);

namespace App\Enums;

enum MetricDimension: string
{
    case Load           = 'load';
    case Reps           = 'reps';
    case Duration       = 'duration';
    case Distance       = 'distance';
    case CardioSettings = 'cardio_settings';
    case Intensity      = 'intensity';
    case IntervalHeader = 'interval_header';

    /**
     * @return string
     */
    public function relation(): string
    {
        return match ($this) {
            self::Load           => 'loadMetric',
            self::Reps           => 'repMetric',
            self::Duration       => 'durationMetric',
            self::Distance       => 'distanceMetric',
            self::CardioSettings => 'cardioSetting',
            self::Intensity      => 'intensityMetric',
            self::IntervalHeader => 'intervalHeader',
        };
    }

    /**
     * Validation fragments keyed relative to metrics.{dimension}. The
     * interval rounds required_with references the absolute input path
     * because Laravel resolves it against the full payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return match ($this) {
            self::Load => [
                'target_weight'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'actual_weight'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'bodyweight_only' => ['sometimes', 'boolean'],
            ],
            self::Reps => [
                'target_reps' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'actual_reps' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'to_failure'  => ['sometimes', 'boolean'],
                'failure_rep' => ['sometimes', 'nullable', 'integer', 'min:1'],
            ],
            self::Duration => [
                'target_duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'actual_duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
            self::Distance => [
                'target_distance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'actual_distance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'lap_count'       => ['sometimes', 'nullable', 'integer', 'min:0'],
                'stroke_count'    => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
            self::CardioSettings => [
                'resistance_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'incline'          => ['sometimes', 'nullable', 'numeric'],
                'speed'            => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'cadence'          => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
            self::Intensity => [
                'rpe'             => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
                'heart_rate_avg'  => ['sometimes', 'nullable', 'integer', 'min:0'],
                'heart_rate_peak' => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
            self::IntervalHeader => [
                'programmed_rounds'            => ['sometimes', 'nullable', 'integer', 'min:1'],
                'completed_rounds'             => ['sometimes', 'nullable', 'integer', 'min:0'],
                'target_work_seconds'          => ['sometimes', 'nullable', 'integer', 'min:0'],
                'target_rest_seconds'          => ['sometimes', 'nullable', 'integer', 'min:0'],
                'rounds'                       => ['sometimes', 'array'],
                'rounds.*.round_number'        => ['required_with:metrics.interval_header.rounds', 'integer', 'min:1'],
                'rounds.*.actual_work_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'rounds.*.actual_rest_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'rounds.*.heart_rate_avg'      => ['sometimes', 'nullable', 'integer', 'min:0'],
                'rounds.*.heart_rate_peak'     => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
        };
    }

    /**
     * Target and definition columns the copy flow duplicates. Actuals never
     * copy; intensity never copies at all (the copy loop skips it).
     *
     * @return list<string>
     */
    public function cloneableColumns(): array
    {
        return match ($this) {
            self::Load           => ['target_weight', 'bodyweight_only'],
            self::Reps           => ['target_reps', 'to_failure'],
            self::Duration       => ['target_duration_seconds'],
            self::Distance       => ['target_distance'],
            self::CardioSettings => ['resistance_level', 'incline', 'speed', 'cadence'],
            self::Intensity      => [],
            self::IntervalHeader => ['programmed_rounds', 'target_work_seconds', 'target_rest_seconds'],
        };
    }

    /** @return list<string> */
    public function apiColumns(): array
    {
        return match ($this) {
            self::Load           => ['target_weight', 'actual_weight', 'bodyweight_only'],
            self::Reps           => ['target_reps', 'actual_reps', 'to_failure', 'failure_rep'],
            self::Duration       => ['target_duration_seconds', 'actual_duration_seconds'],
            self::Distance       => ['target_distance', 'actual_distance', 'lap_count', 'stroke_count'],
            self::CardioSettings => ['resistance_level', 'incline', 'speed', 'cadence'],
            self::Intensity      => ['rpe', 'heart_rate_avg', 'heart_rate_peak'],
            self::IntervalHeader => ['programmed_rounds', 'completed_rounds', 'target_work_seconds', 'target_rest_seconds'],
        };
    }
}
