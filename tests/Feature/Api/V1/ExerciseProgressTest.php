<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\WorkoutEntry;

class ExerciseProgressTest extends TestCase
{
    use RefreshDatabase;

    private function createExercise(
        ?User $user,
        string $type = 'resistance',
        array $overrides = [],
        array $typeAttributes = [],
    ): Exercise {
        $exercise = Exercise::create(array_merge([
            'name' => 'Test Exercise',
            'type' => $type,
            'user_id' => $user?->id,
        ], $overrides));

        $defaults = match ($type) {
            'resistance' => [],
            'timed_hold' => [],
            'distance' => ['distance_unit' => 'meters'],
            'interval' => [],
        };

        $childRelation = match ($type) {
            'resistance' => 'resistance',
            'timed_hold' => 'timedHold',
            'distance' => 'distance',
            'interval' => 'interval',
        };

        $exercise->$childRelation()->create(array_merge($defaults, $typeAttributes));

        return $exercise;
    }

    /** @return array{entry: WorkoutEntry, workout: Workout} */
    private function createEntryWithMetrics(
        User $user,
        Exercise $exercise,
        string $date,
        array $metrics = [],
        int $setOrder = 0,
    ): array {
        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
        ]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => $setOrder,
        ]);

        if (isset($metrics['load'])) {
            $entry->loadMetric()->create($metrics['load']);
        }
        if (isset($metrics['reps'])) {
            $entry->repMetric()->create($metrics['reps']);
        }
        if (isset($metrics['duration'])) {
            $entry->durationMetric()->create($metrics['duration']);
        }
        if (isset($metrics['distance'])) {
            $entry->distanceMetric()->create($metrics['distance']);
        }
        if (isset($metrics['interval_header'])) {
            $entry->intervalHeader()->create($metrics['interval_header']);
        }

        return ['entry' => $entry, 'workout' => $workout];
    }

    // -- Progress: Resistance --

    public function test_progress_returns_resistance_data_points(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 135.00],
            'reps' => ['actual_reps' => 10],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'load' => ['actual_weight' => 145.00],
            'reps' => ['actual_reps' => 8],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.exercise_id', $exercise->id)
            ->assertJsonPath('data.exercise_type', 'resistance')
            ->assertJsonPath('data.range', 'all')
            ->assertJsonPath('data.primary_metric', 'weight')
            ->assertJsonCount(2, 'data.data_points')
            ->assertJsonPath('data.data_points.0.entry_id', $r1['entry']->id)
            ->assertJsonPath('data.data_points.0.date', '2026-05-01')
            ->assertJsonPath('data.data_points.0.value', 135.0)
            ->assertJsonPath('data.data_points.1.entry_id', $r2['entry']->id)
            ->assertJsonPath('data.data_points.1.value', 145.0);
    }

    // -- Progress: Timed Hold --

    public function test_progress_returns_timed_hold_data_points(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'timed_hold', ['name' => 'Plank']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'duration' => ['actual_duration_seconds' => 60],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'duration' => ['actual_duration_seconds' => 90],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.primary_metric', 'duration')
            ->assertJsonCount(2, 'data.data_points')
            ->assertJsonPath('data.data_points.0.value', 60)
            ->assertJsonPath('data.data_points.1.value', 90);
    }

    // -- Progress: Distance --

    public function test_progress_returns_distance_data_points(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'distance', ['name' => 'Treadmill Run']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'distance' => ['actual_distance' => 3.10, 'distance_unit' => 'miles'],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'distance' => ['actual_distance' => 5.00, 'distance_unit' => 'miles'],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.primary_metric', 'distance')
            ->assertJsonCount(2, 'data.data_points')
            ->assertJsonPath('data.data_points.0.value', 3.1)
            ->assertJsonPath('data.data_points.1.value', 5.0);
    }

    // -- Progress: Interval --

    public function test_progress_returns_interval_data_points(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'interval', ['name' => 'HIIT']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'interval_header' => ['completed_rounds' => 6, 'programmed_rounds' => 8],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'interval_header' => ['completed_rounds' => 8, 'programmed_rounds' => 8],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.primary_metric', 'completed_rounds')
            ->assertJsonCount(2, 'data.data_points')
            ->assertJsonPath('data.data_points.0.value', 6)
            ->assertJsonPath('data.data_points.1.value', 8);
    }

    // -- Progress: No volume key for non-resistance --

    public function test_progress_omits_volume_for_non_resistance(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'timed_hold', ['name' => 'Plank']);

        $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'duration' => ['actual_duration_seconds' => 60],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk();
        $this->assertArrayNotHasKey('volume', $response->json('data'));
    }

    // -- PR Detection --

    public function test_progress_flags_prs_on_new_maxes(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Squat']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-04-01', [
            'load' => ['actual_weight' => 135.00],
            'reps' => ['actual_reps' => 5],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-04-15', [
            'load' => ['actual_weight' => 155.00],
            'reps' => ['actual_reps' => 5],
        ]);
        $r3 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 145.00],
            'reps' => ['actual_reps' => 5],
        ]);
        $r4 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'load' => ['actual_weight' => 175.00],
            'reps' => ['actual_reps' => 3],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.data_points.0.is_pr', true)
            ->assertJsonPath('data.data_points.1.is_pr', true)
            ->assertJsonPath('data.data_points.2.is_pr', false)
            ->assertJsonPath('data.data_points.3.is_pr', true);
    }

    public function test_pr_detection_uses_all_time_history_not_just_range(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Deadlift']);

        $this->createEntryWithMetrics($user, $exercise, Carbon::now()->subDays(60)->toDateString(), [
            'load' => ['actual_weight' => 200.00],
            'reps' => ['actual_reps' => 5],
        ]);

        $r2 = $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subDays(10)->toDateString(),
            [
                'load' => ['actual_weight' => 185.00],
                'reps' => ['actual_reps' => 5],
            ],
        );

        $r3 = $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subDays(5)->toDateString(),
            [
                'load' => ['actual_weight' => 225.00],
                'reps' => ['actual_reps' => 3],
            ],
        );

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=1m");

        $response->assertOk()
            ->assertJsonCount(2, 'data.data_points')
            ->assertJsonPath('data.data_points.0.is_pr', false)
            ->assertJsonPath('data.data_points.1.is_pr', true);
    }

    // -- Time Range Filtering --

    public function test_progress_filters_by_1m_range(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Curl']);

        $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subDays(45)->toDateString(),
            ['load' => ['actual_weight' => 20.00], 'reps' => ['actual_reps' => 10]],
        );

        $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subDays(15)->toDateString(),
            ['load' => ['actual_weight' => 25.00], 'reps' => ['actual_reps' => 10]],
        );

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=1m");

        $response->assertOk()
            ->assertJsonCount(1, 'data.data_points')
            ->assertJsonPath('data.data_points.0.value', 25.0);
    }

    public function test_progress_returns_all_data_for_all_range(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Press']);

        $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subYear()->subMonth()->toDateString(),
            ['load' => ['actual_weight' => 95.00], 'reps' => ['actual_reps' => 10]],
        );
        $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subDays(5)->toDateString(),
            ['load' => ['actual_weight' => 135.00], 'reps' => ['actual_reps' => 8]],
        );

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonCount(2, 'data.data_points');
    }

    public function test_progress_defaults_to_all_when_range_omitted(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Lat Pull']);

        $this->createEntryWithMetrics(
            $user,
            $exercise,
            Carbon::now()->subYear()->subMonth()->toDateString(),
            ['load' => ['actual_weight' => 100.00], 'reps' => ['actual_reps' => 8]],
        );

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress");

        $response->assertOk()
            ->assertJsonPath('data.range', 'all')
            ->assertJsonCount(1, 'data.data_points');
    }

    // -- Volume --

    public function test_progress_includes_volume_for_resistance(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Press Volume']);

        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
        ]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);
        $entry1->loadMetric()->create(['actual_weight' => 135.00]);
        $entry1->repMetric()->create(['actual_reps' => 10]);

        $entry2 = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 1,
        ]);
        $entry2->loadMetric()->create(['actual_weight' => 155.00]);
        $entry2->repMetric()->create(['actual_reps' => 8]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonCount(1, 'data.volume')
            ->assertJsonPath('data.volume.0.workout_id', $workout->id)
            ->assertJsonPath('data.volume.0.date', '2026-05-01')
            ->assertJsonPath('data.volume.0.total_volume', 2590.0);
    }

    // -- Records: Resistance --

    public function test_records_returns_resistance_bests(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Records']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-04-01', [
            'load' => ['actual_weight' => 135.00],
            'reps' => ['actual_reps' => 12],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 185.00],
            'reps' => ['actual_reps' => 5],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk()
            ->assertJsonPath('data.exercise_id', $exercise->id)
            ->assertJsonPath('data.exercise_type', 'resistance')
            ->assertJsonPath('data.records.weight.value', 185.0)
            ->assertJsonPath('data.records.weight.entry_id', $r2['entry']->id)
            ->assertJsonPath('data.records.weight.date', '2026-05-01')
            ->assertJsonPath('data.records.reps.value', 12)
            ->assertJsonPath('data.records.reps.entry_id', $r1['entry']->id)
            ->assertJsonPath('data.records.reps.date', '2026-04-01')
            ->assertJsonPath('data.records.volume.value', 1620.0)
            ->assertJsonPath('data.records.volume.entry_id', $r1['entry']->id);
    }

    // -- Records: Timed Hold --

    public function test_records_returns_timed_hold_bests(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'timed_hold', ['name' => 'Plank Records']);

        $this->createEntryWithMetrics($user, $exercise, '2026-04-01', [
            'duration' => ['actual_duration_seconds' => 60],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'duration' => ['actual_duration_seconds' => 120],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk()
            ->assertJsonPath('data.records.duration.value', 120)
            ->assertJsonPath('data.records.duration.entry_id', $r2['entry']->id);
    }

    // -- Records: Distance --

    public function test_records_returns_distance_bests(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'distance', ['name' => 'Rowing Records']);

        $this->createEntryWithMetrics($user, $exercise, '2026-04-01', [
            'distance' => ['actual_distance' => 2.00, 'distance_unit' => 'kilometers'],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'distance' => ['actual_distance' => 5.50, 'distance_unit' => 'kilometers'],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk()
            ->assertJsonPath('data.records.distance.value', 5.5)
            ->assertJsonPath('data.records.distance.entry_id', $r2['entry']->id);
    }

    // -- Records: Interval --

    public function test_records_returns_interval_bests(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'interval', ['name' => 'Tabata Records']);

        $this->createEntryWithMetrics($user, $exercise, '2026-04-01', [
            'interval_header' => ['completed_rounds' => 4, 'programmed_rounds' => 8],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'interval_header' => ['completed_rounds' => 8, 'programmed_rounds' => 8],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk()
            ->assertJsonPath('data.records.completed_rounds.value', 8)
            ->assertJsonPath('data.records.completed_rounds.entry_id', $r2['entry']->id);
    }

    // -- Bodyweight Exercises --

    public function test_progress_uses_reps_as_primary_metric_for_bodyweight_only(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise(
            $user,
            'resistance',
            ['name' => 'Push-ups'],
            ['bodyweight_base' => true, 'allows_added_weight' => false],
        );

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 0, 'bodyweight_only' => true],
            'reps' => ['actual_reps' => 20],
        ]);
        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'load' => ['actual_weight' => 0, 'bodyweight_only' => true],
            'reps' => ['actual_reps' => 30],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.primary_metric', 'reps')
            ->assertJsonPath('data.data_points.0.value', 20)
            ->assertJsonPath('data.data_points.1.value', 30)
            ->assertJsonPath('data.data_points.1.is_pr', true);
    }

    public function test_progress_uses_weight_for_weighted_bodyweight_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise(
            $user,
            'resistance',
            ['name' => 'Weighted Pull-ups'],
            ['bodyweight_base' => true, 'allows_added_weight' => true],
        );

        $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 25.00],
            'reps' => ['actual_reps' => 8],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonPath('data.primary_metric', 'weight')
            ->assertJsonPath('data.data_points.0.value', 25.0);
    }

    public function test_records_omits_weight_record_for_bodyweight_only(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise(
            $user,
            'resistance',
            ['name' => 'Push-ups Records'],
            ['bodyweight_base' => true, 'allows_added_weight' => false],
        );

        $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 0, 'bodyweight_only' => true],
            'reps' => ['actual_reps' => 25],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk();
        $this->assertArrayNotHasKey('weight', $response->json('data.records'));
        $this->assertArrayHasKey('reps', $response->json('data.records'));
        $response->assertJsonPath('data.records.reps.value', 25);
    }

    // -- Authorization --

    public function test_progress_returns_401_for_unauthenticated(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');

        $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all")
            ->assertUnauthorized();
    }

    public function test_progress_returns_403_for_other_users_exercise(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance', ['name' => 'Private Exercise']);

        Passport::actingAs($other);

        $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all")
            ->assertForbidden();
    }

    public function test_progress_allows_system_exercise_scoped_to_user_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exercise = $this->createExercise(null, 'resistance', ['name' => 'System Bench']);

        $r1 = $this->createEntryWithMetrics($user, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 135.00],
            'reps' => ['actual_reps' => 10],
        ]);

        $this->createEntryWithMetrics($other, $exercise, '2026-05-01', [
            'load' => ['actual_weight' => 225.00],
            'reps' => ['actual_reps' => 5],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonCount(1, 'data.data_points')
            ->assertJsonPath('data.data_points.0.value', 135.0);
    }

    public function test_records_returns_403_for_other_users_exercise(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance');

        Passport::actingAs($other);

        $this->getJson("/api/v1/exercises/{$exercise->id}/records")
            ->assertForbidden();
    }

    // -- Edge Cases --

    public function test_progress_returns_empty_data_points_for_no_entries(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'New Exercise']);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonCount(0, 'data.data_points');
    }

    public function test_progress_excludes_entries_with_null_actuals(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Null']);

        $workout = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-05-01']);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);
        $entry->loadMetric()->create(['target_weight' => 135.00, 'actual_weight' => null]);

        $r2 = $this->createEntryWithMetrics($user, $exercise, '2026-05-15', [
            'load' => ['actual_weight' => 145.00],
            'reps' => ['actual_reps' => 8],
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=all");

        $response->assertOk()
            ->assertJsonCount(1, 'data.data_points')
            ->assertJsonPath('data.data_points.0.entry_id', $r2['entry']->id);
    }

    public function test_records_returns_empty_for_no_entries(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}/records");

        $response->assertOk()
            ->assertJsonPath('data.records', []);
    }

    public function test_progress_returns_422_for_invalid_range(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');

        Passport::actingAs($user);

        $this->getJson("/api/v1/exercises/{$exercise->id}/progress?range=invalid")
            ->assertStatus(422)
            ->assertJsonValidationErrors('range');
    }
}
