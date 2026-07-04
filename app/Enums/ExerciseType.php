<?php

declare(strict_types=1);

namespace App\Enums;

enum ExerciseType: string
{
    case Resistance = 'resistance';
    case TimedHold = 'timed_hold';
    case Distance = 'distance';
    case Interval = 'interval';

    /**
     * Expected dimensions per ADR-006. Documented mapping only: presence is
     * never enforced because ad-hoc and template entries carry partial sets.
     *
     * @return list<MetricDimension>
     */
    public function requiredMetrics(): array
    {
        return match ($this) {
            self::Resistance => [MetricDimension::Load, MetricDimension::Reps],
            self::TimedHold => [MetricDimension::Duration],
            self::Distance => [MetricDimension::Distance],
            self::Interval => [MetricDimension::IntervalHeader],
        };
    }

    /** @return list<MetricDimension> */
    public function allowedMetrics(): array
    {
        $optional = match ($this) {
            self::Resistance => [MetricDimension::Intensity],
            self::TimedHold => [MetricDimension::Load, MetricDimension::Intensity],
            self::Distance => [MetricDimension::Duration, MetricDimension::CardioSettings, MetricDimension::Intensity],
            self::Interval => [MetricDimension::CardioSettings, MetricDimension::Distance, MetricDimension::Intensity],
        };

        return [...$this->requiredMetrics(), ...$optional];
    }

    /** The CTI child relation name on the Exercise model. */
    public function childRelation(): string
    {
        return match ($this) {
            self::Resistance => 'resistance',
            self::TimedHold => 'timedHold',
            self::Distance => 'distance',
            self::Interval => 'interval',
        };
    }

    /** @return list<string> */
    public static function childRelations(): array
    {
        return array_map(fn (self $type) => $type->childRelation(), self::cases());
    }
}
