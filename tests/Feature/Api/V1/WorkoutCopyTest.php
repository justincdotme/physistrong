<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EntryGroup;
use App\Models\Exercise;
use App\Models\LogIntervalHeader;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkoutCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_copies_workout_with_new_date(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench Press']);
        $workout  = $this->createWorkoutWithExercises($user, [$exercise]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Source Workout')
            ->assertJsonPath('data.date', '2026-07-01');

        $this->assertDatabaseCount('workouts', 2);
    }

    public function test_copy_allows_name_override(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = $this->createWorkoutWithExercises($user, [$exercise]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
            'name' => 'My Custom Name',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My Custom Name');
    }

    public function test_copy_preserves_exercise_order(): void
    {
        $user    = User::factory()->create();
        $ex1     = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Squat']);
        $ex2     = Exercise::factory()->timedHold()->create(['user_id' => $user->id, 'name' => 'Plank']);
        $workout = $this->createWorkoutWithExercises($user, [$ex1, $ex2]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyId = $response->json('data.id');

        $this->assertDatabaseHas('exercise_workout', [
            'workout_id'     => $copyId,
            'exercise_id'    => $ex1->id,
            'exercise_order' => 0,
        ]);
        $this->assertDatabaseHas('exercise_workout', [
            'workout_id'     => $copyId,
            'exercise_id'    => $ex2->id,
            'exercise_order' => 1,
        ]);
    }

    public function test_copy_clones_entry_groups(): void
    {
        $user = User::factory()->create();
        $ex1  = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $ex2  = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Row']);

        $workout = Workout::create([
            'name'    => 'Superset Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $group = $workout->groups()->create([
            'name'                           => 'Push/Pull',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 90,
        ]);

        $workout->exercises()->attach($ex1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($ex2->id, ['exercise_order' => 1]);

        $setOrder = 0;

        for ($round = 1; $round <= 3; $round++) {
            $workout->entries()->create([
                'exercise_id'    => $ex1->id,
                'set_order'      => $setOrder++,
                'entry_group_id' => $group->id,
                'group_round'    => $round,
            ]);
            $workout->entries()->create([
                'exercise_id'    => $ex2->id,
                'set_order'      => $setOrder++,
                'entry_group_id' => $group->id,
                'group_round'    => $round,
            ]);
        }

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201);
        $copyId = $response->json('data.id');

        $this->assertDatabaseHas('entry_groups', [
            'workout_id'                     => $copyId,
            'name'                           => 'Push/Pull',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 90,
        ]);

        $copyGroupId = EntryGroup::where('workout_id', $copyId)->first()->id;

        $this->assertDatabaseCount('workout_entries', 12);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id'     => $copyId,
            'exercise_id'    => $ex1->id,
            'entry_group_id' => $copyGroupId,
            'group_round'    => 1,
        ]);
    }

    public function test_copy_clones_metric_targets_and_nulls_actuals(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Squat']);

        $workout = Workout::create([
            'name'    => 'Leg Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $entry->loadMetric()->create([
            'target_weight'   => 225.00,
            'actual_weight'   => 225.00,
            'bodyweight_only' => false,
        ]);
        $entry->repMetric()->create([
            'target_reps' => 5,
            'actual_reps' => 4,
            'to_failure'  => false,
            'failure_rep' => 4,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201);

        $copyEntryId = $response->json('data.entries.0.id');

        $this->assertDatabaseHas('log_load_metrics', [
            'entry_id'        => $copyEntryId,
            'target_weight'   => 225.00,
            'actual_weight'   => null,
            'bodyweight_only' => false,
        ]);
        $this->assertDatabaseHas('log_rep_metrics', [
            'entry_id'    => $copyEntryId,
            'target_reps' => 5,
            'actual_reps' => null,
            'to_failure'  => false,
            'failure_rep' => null,
        ]);
    }

    public function test_copy_clones_cardio_settings(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->distance()->create(['user_id' => $user->id, 'name' => 'Treadmill Run']);

        $workout = Workout::create([
            'name'    => 'Cardio Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $entry->cardioSetting()->create([
            'resistance_level' => 5,
            'incline'          => 2.5,
            'speed'            => 6.0,
            'cadence'          => 170,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyEntryId = $response->json('data.entries.0.id');

        $this->assertDatabaseHas('log_cardio_settings', [
            'entry_id'         => $copyEntryId,
            'resistance_level' => 5,
            'incline'          => 2.5,
            'speed'            => 6.0,
            'cadence'          => 170,
        ]);
    }

    public function test_copy_clones_interval_header_without_rounds(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->interval()->create(['user_id' => $user->id, 'name' => 'HIIT Sprint']);

        $workout = Workout::create([
            'name'    => 'Interval Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $header = $entry->intervalHeader()->create([
            'programmed_rounds'   => 8,
            'completed_rounds'    => 8,
            'target_work_seconds' => 30,
            'target_rest_seconds' => 60,
        ]);
        $header->rounds()->create([
            'round_number'        => 1,
            'actual_work_seconds' => 32,
            'actual_rest_seconds' => 58,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyEntryId = $response->json('data.entries.0.id');

        $this->assertDatabaseHas('log_interval_headers', [
            'entry_id'            => $copyEntryId,
            'programmed_rounds'   => 8,
            'completed_rounds'    => null,
            'target_work_seconds' => 30,
            'target_rest_seconds' => 60,
        ]);

        $copyHeaderId = LogIntervalHeader::where('entry_id', $copyEntryId)->first()->id;
        $this->assertDatabaseMissing('log_interval_rounds', [
            'interval_header_id' => $copyHeaderId,
        ]);
    }

    public function test_copy_skips_intensity_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Deadlift']);

        $workout = Workout::create([
            'name'    => 'Heavy Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $entry->intensityMetric()->create([
            'rpe'             => 9,
            'heart_rate_avg'  => 155,
            'heart_rate_peak' => 178,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyEntryId = $response->json('data.entries.0.id');

        $this->assertDatabaseMissing('log_intensity_metrics', [
            'entry_id' => $copyEntryId,
        ]);
    }

    public function test_copy_preserves_entry_notes(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);

        $workout = Workout::create([
            'name'    => 'Push Day',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'notes'       => 'Use fat grip attachment',
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyId = $response->json('data.id');

        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $copyId,
            'notes'      => 'Use fat grip attachment',
        ]);
    }

    public function test_copy_does_not_carry_exhaustion_or_soreness(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);

        $workout = Workout::create([
            'name'       => 'Hard Session',
            'user_id'    => $user->id,
            'date'       => '2026-06-01',
            'exhaustion' => 8,
            'soreness'   => 6,
        ]);

        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.exhaustion', null)
            ->assertJsonPath('data.soreness', null);
    }

    public function test_copied_workout_is_independent_of_source(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = $this->createWorkoutWithExercises($user, [$exercise]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ]);

        $copyId = $response->json('data.id');

        $this->deleteJson("/api/v1/workouts/{$workout->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('workouts', ['id' => $copyId]);
        $this->assertDatabaseHas('exercise_workout', ['workout_id' => $copyId]);
        $this->assertDatabaseHas('workout_entries', ['workout_id' => $copyId]);
    }

    public function test_cannot_copy_another_users_workout(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id]);
        $workout  = $this->createWorkoutWithExercises($owner, [$exercise]);

        Passport::actingAs($other);

        $this->postJson("/api/v1/workouts/{$workout->id}/copy", [
            'date' => '2026-07-01',
        ])->assertStatus(403);
    }

    public function test_copy_validates_date_required(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = $this->createWorkoutWithExercises($user, [$exercise]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/copy", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @param list<Exercise> $exercises */
    private function createWorkoutWithExercises(User $user, array $exercises): Workout
    {
        $workout = Workout::create([
            'name'    => 'Source Workout',
            'user_id' => $user->id,
            'date'    => '2026-06-01',
        ]);

        foreach ($exercises as $i => $exercise) {
            $workout->exercises()->attach($exercise->id, ['exercise_order' => $i]);
            $workout->entries()->create([
                'exercise_id' => $exercise->id,
                'set_order'   => $i,
            ]);
        }

        return $workout;
    }
}
