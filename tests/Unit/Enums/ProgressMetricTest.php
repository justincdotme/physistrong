<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ExerciseType;
use App\Enums\ProgressMetric;
use App\Models\Exercise;
use App\Models\ExerciseResistance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProgressMetricTest extends TestCase
{
    /** @return array<string, array<mixed>> */
    public static function typeMetricProvider(): array
    {
        return [
            'resistance charts weight then reps'     => [ExerciseType::Resistance, ['weight', 'reps']],
            'timed hold charts duration then weight' => [ExerciseType::TimedHold, ['duration', 'weight']],
            'distance charts distance then duration' => [ExerciseType::Distance, ['distance', 'duration']],
            'interval charts rounds then distance'   => [ExerciseType::Interval, ['completed_rounds', 'distance']],
        ];
    }

    /** @param list<string> $expected */
    #[DataProvider('typeMetricProvider')]
    public function test_for_exercise_lists_chartable_metrics_primary_first(
        ExerciseType $type,
        array $expected,
    ): void {
        $exercise       = new Exercise;
        $exercise->type = $type;

        $values = array_map(fn (ProgressMetric $m) => $m->value, ProgressMetric::forExercise($exercise));

        $this->assertSame($expected, $values);
    }

    public function test_for_exercise_drops_weight_for_bodyweight_only_lifts(): void
    {
        $exercise = $this->resistanceExercise(bodyweightBase: true, allowsAddedWeight: false);

        $values = array_map(fn (ProgressMetric $m) => $m->value, ProgressMetric::forExercise($exercise));

        $this->assertSame(['reps'], $values);
        $this->assertSame(ProgressMetric::Reps, ProgressMetric::primaryFor($exercise));
    }

    public function test_for_exercise_keeps_weight_when_added_weight_is_allowed(): void
    {
        $exercise = $this->resistanceExercise(bodyweightBase: true, allowsAddedWeight: true);

        $this->assertSame(ProgressMetric::Weight, ProgressMetric::primaryFor($exercise));
    }

    public function test_cast_value_matches_the_column_type(): void
    {
        $this->assertSame(2400, ProgressMetric::Duration->castValue('2400'));
        $this->assertSame(4.8, ProgressMetric::Distance->castValue('4.80'));
    }

    private function resistanceExercise(bool $bodyweightBase, bool $allowsAddedWeight): Exercise
    {
        $exercise       = new Exercise;
        $exercise->type = ExerciseType::Resistance;
        $exercise->setRelation('resistance', new ExerciseResistance([
            'bodyweight_base'     => $bodyweightBase,
            'allows_added_weight' => $allowsAddedWeight,
        ]));

        return $exercise;
    }
}
