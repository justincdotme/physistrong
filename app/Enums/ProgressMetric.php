<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Exercise;

/**
 * Metrics the progress charts can plot. Adding a case here, plus its display
 * entry in the SPA, is all it takes to chart a new dimension.
 */
enum ProgressMetric: string
{
    case Weight          = 'weight';
    case Reps            = 'reps';
    case Duration        = 'duration';
    case Distance        = 'distance';
    case CompletedRounds = 'completed_rounds';

    /**
     * Chartable metrics for an exercise, primary first, derived from the
     * dimensions its type accepts rather than from a per-type list.
     *
     * @param Exercise $exercise
     *
     * @return list<self>
     */
    public static function forExercise(Exercise $exercise): array
    {
        $allowed = $exercise->type->allowedMetrics();
        $primary = self::primaryFor($exercise);

        $rest = array_values(array_filter(
            self::cases(),
            static fn (self $metric): bool => $metric !== $primary
                && in_array($metric->dimension(), $allowed, true)
                && $metric->appliesTo($exercise),
        ));

        return [$primary, ...$rest];
    }

    /**
     * @param Exercise $exercise
     *
     * @return self
     */
    public static function primaryFor(Exercise $exercise): self
    {
        if ($exercise->type === ExerciseType::Resistance) {
            return self::Weight->appliesTo($exercise) ? self::Weight : self::Reps;
        }

        return match ($exercise->type) {
            ExerciseType::TimedHold => self::Duration,
            ExerciseType::Distance  => self::Distance,
            ExerciseType::Interval  => self::CompletedRounds,
        };
    }

    /**
     * @return MetricDimension
     */
    public function dimension(): MetricDimension
    {
        return match ($this) {
            self::Weight          => MetricDimension::Load,
            self::Reps            => MetricDimension::Reps,
            self::Duration        => MetricDimension::Duration,
            self::Distance        => MetricDimension::Distance,
            self::CompletedRounds => MetricDimension::IntervalHeader,
        };
    }

    /**
     * @return string
     */
    public function table(): string
    {
        return match ($this) {
            self::Weight          => 'log_load_metrics',
            self::Reps            => 'log_rep_metrics',
            self::Duration        => 'log_duration_metrics',
            self::Distance        => 'log_distance_metrics',
            self::CompletedRounds => 'log_interval_headers',
        };
    }

    /**
     * @return string
     */
    public function column(): string
    {
        return match ($this) {
            self::Weight          => 'actual_weight',
            self::Reps            => 'actual_reps',
            self::Duration        => 'actual_duration_seconds',
            self::Distance        => 'actual_distance',
            self::CompletedRounds => 'completed_rounds',
        };
    }

    /**
     * @param mixed $value
     *
     * @return float|integer
     */
    public function castValue(mixed $value): float|int
    {
        return match ($this) {
            self::Weight, self::Distance => (float) $value,
            default                      => (int) $value,
        };
    }

    /**
     * A bodyweight-only lift stores nothing but zero in the load column, so
     * charting it would draw a flat line at the origin.
     *
     * @param Exercise $exercise
     *
     * @return boolean
     */
    private function appliesTo(Exercise $exercise): bool
    {
        if ($this !== self::Weight || $exercise->type !== ExerciseType::Resistance) {
            return true;
        }

        $resistance = $exercise->resistance;

        return ! ($resistance && $resistance->bodyweight_base && ! $resistance->allows_added_weight);
    }
}
