<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ExerciseType;
use App\Models\EntryGroup;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkoutEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function disallowedMetricProvider(): array
    {
        return [
            'resistance rejects interval_header' => ['resistance', 'interval_header'],
            'resistance rejects distance'        => ['resistance', 'distance'],
            'resistance rejects cardio_settings' => ['resistance', 'cardio_settings'],
            'timed_hold rejects reps'            => ['timed_hold', 'reps'],
            'timed_hold rejects distance'        => ['timed_hold', 'distance'],
            'distance rejects load'              => ['distance', 'load'],
            'distance rejects reps'              => ['distance', 'reps'],
            'interval rejects load'              => ['interval', 'load'],
            'interval rejects reps'              => ['interval', 'reps'],
        ];
    }

    // Store

    public function test_creates_entry_with_resistance_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench Press']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'load' => [
                    'target_weight' => 185.5,
                    'actual_weight' => 180.0,
                ],
                'reps' => [
                    'target_reps' => 10,
                    'actual_reps' => 8,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metrics.load.target_weight', '185.50')
            ->assertJsonPath('data.metrics.load.actual_weight', '180.00')
            ->assertJsonPath('data.metrics.reps.target_reps', 10)
            ->assertJsonPath('data.metrics.reps.actual_reps', 8);

        $this->assertDatabaseHas('workout_entries', [
            'workout_id'  => $workout->id,
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);
    }

    public function test_creates_entry_with_duration_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->timedHold()->create(['user_id' => $user->id, 'name' => 'Plank']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'duration' => [
                    'target_duration_seconds' => 120,
                    'actual_duration_seconds' => 115,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metrics.duration.target_duration_seconds', 120)
            ->assertJsonPath('data.metrics.duration.actual_duration_seconds', 115);
    }

    public function test_creates_entry_with_distance_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->distance()->create(['user_id' => $user->id, 'name' => 'Run']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'distance' => [
                    'target_distance' => 5.0,
                    'actual_distance' => 4.8,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metrics.distance.target_distance', '5.00')
            ->assertJsonPath('data.metrics.distance.actual_distance', '4.80');
    }

    public function test_ignores_submitted_distance_unit_metric(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->distance()->create(['user_id' => $user->id, 'name' => 'Row']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'distance' => [
                    'target_distance' => 5.0,
                    'distance_unit'   => 'kilometers',
                ],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.metrics.distance.target_distance', '5.00')
            ->assertJsonMissingPath('data.metrics.distance.distance_unit');
    }

    public function test_creates_entry_with_interval_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->interval()->create(['user_id' => $user->id, 'name' => 'HIIT']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'interval_header' => [
                    'programmed_rounds'   => 8,
                    'completed_rounds'    => 8,
                    'target_work_seconds' => 30,
                    'target_rest_seconds' => 15,
                    'rounds'              => [
                        [
                            'round_number'        => 1,
                            'actual_work_seconds' => 30,
                            'actual_rest_seconds' => 15,
                        ],
                        [
                            'round_number'        => 2,
                            'actual_work_seconds' => 31,
                            'actual_rest_seconds' => 14,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metrics.interval_header.programmed_rounds', 8)
            ->assertJsonPath('data.metrics.interval_header.completed_rounds', 8)
            ->assertJsonPath('data.metrics.interval_header.rounds.0.round_number', 1)
            ->assertJsonPath('data.metrics.interval_header.rounds.1.round_number', 2);
    }

    public function test_creates_entry_without_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.metrics', []);
    }

    #[DataProvider('disallowedMetricProvider')]
    public function test_store_rejects_metric_not_allowed_for_exercise_type(string $type, string $metricKey): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->{ExerciseType::from($type)->childRelation()}()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [$metricKey => []],
        ])->assertStatus(422)
            ->assertJsonValidationErrors("metrics.{$metricKey}");
    }

    public function test_store_accepts_optional_metric_for_exercise_type(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->timedHold()->create(['user_id' => $user->id, 'name' => 'Weighted Plank']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => [
                'duration' => ['actual_duration_seconds' => 60],
                'load'     => ['actual_weight' => 25.0],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.metrics.duration.actual_duration_seconds', 60)
            ->assertJsonPath('data.metrics.load.actual_weight', '25.00');
    }

    public function test_store_rejects_unknown_metric_key(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'metrics'     => ['bogus' => ['value' => 1]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('metrics.bogus');
    }

    public function test_update_rejects_metric_not_allowed_for_exercise_type(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'metrics' => ['interval_header' => ['programmed_rounds' => 4]],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('metrics.interval_header');
    }

    public function test_validates_exercise_id_required(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'set_order' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');
    }

    public function test_validates_set_order_required(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('set_order');
    }

    // Update

    public function test_updates_entry_base_fields(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
            'notes'       => 'Old notes',
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'set_order' => 1,
            'notes'     => 'New notes',
        ])->assertOk()
            ->assertJsonPath('data.set_order', 1)
            ->assertJsonPath('data.notes', 'New notes');
    }

    public function test_updates_entry_metrics_via_upsert(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $entry->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 95,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'set_order' => 0,
            'metrics'   => [
                'load' => [
                    'target_weight' => 110,
                    'actual_weight' => 105,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.metrics.load.target_weight', '110.00')
            ->assertJsonPath('data.metrics.load.actual_weight', '105.00');

        $this->assertDatabaseMissing('log_load_metrics', [
            'entry_id'      => $entry->id,
            'target_weight' => 100,
        ]);

        $this->assertDatabaseHas('log_load_metrics', [
            'entry_id'      => $entry->id,
            'target_weight' => 110,
        ]);
    }

    public function test_adds_new_metric_on_update(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $entry->loadMetric()->create([
            'target_weight' => 100,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'set_order' => 0,
            'metrics'   => [
                'load' => [
                    'target_weight' => 100,
                ],
                'reps' => [
                    'target_reps' => 10,
                    'actual_reps' => 8,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.metrics.load.target_weight', '100.00')
            ->assertJsonPath('data.metrics.reps.target_reps', 10)
            ->assertJsonPath('data.metrics.reps.actual_reps', 8);

        $this->assertDatabaseHas('log_load_metrics', ['entry_id' => $entry->id]);
        $this->assertDatabaseHas('log_rep_metrics', ['entry_id' => $entry->id]);
    }

    // Destroy

    public function test_deletes_entry(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workout_entries', ['id' => $entry->id]);
    }

    public function test_delete_cascades_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $loadMetric = $entry->loadMetric()->create(['target_weight' => 100]);
        $repMetric  = $entry->repMetric()->create(['target_reps' => 10]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workout_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('log_load_metrics', ['id' => $loadMetric->id]);
        $this->assertDatabaseMissing('log_rep_metrics', ['id' => $repMetric->id]);
    }

    // Reorder

    public function test_reorders_entries(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);
        $entry3 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/reorder", [
            'ids' => [$entry3->id, $entry1->id, $entry2->id],
        ])->assertOk();

        $this->assertDatabaseHas('workout_entries', [
            'id'        => $entry3->id,
            'set_order' => 0,
        ]);

        $this->assertDatabaseHas('workout_entries', [
            'id'        => $entry1->id,
            'set_order' => 1,
        ]);

        $this->assertDatabaseHas('workout_entries', [
            'id'        => $entry2->id,
            'set_order' => 2,
        ]);
    }

    public function test_reorder_rejects_nonexistent_entry_id(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/reorder", [
            'ids' => [999999, $entry1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('workout_entries', ['id' => $entry1->id, 'set_order' => 0]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry2->id, 'set_order' => 1]);
    }

    public function test_reorder_rejects_entry_from_other_workout(): void
    {
        $user         = User::factory()->create();
        $exercise     = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout      = Workout::factory()->create(['user_id' => $user->id]);
        $otherWorkout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $otherWorkout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1       = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2       = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);
        $foreignEntry = $otherWorkout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/reorder", [
            'ids' => [$foreignEntry->id, $entry1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('workout_entries', ['id' => $entry1->id, 'set_order' => 0]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry2->id, 'set_order' => 1]);
    }

    public function test_reorder_rejects_duplicate_ids(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/reorder", [
            'ids' => [$entry1->id, $entry1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('workout_entries', ['id' => $entry1->id, 'set_order' => 0]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry2->id, 'set_order' => 1]);
    }

    public function test_reorder_rejects_partial_id_list(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);
        $entry3 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/reorder", [
            'ids' => [$entry3->id, $entry1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids');

        $this->assertDatabaseHas('workout_entries', ['id' => $entry1->id, 'set_order' => 0]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry2->id, 'set_order' => 1]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry3->id, 'set_order' => 2]);
    }

    // Scoped Binding

    public function test_update_returns_404_for_entry_from_other_workout(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);

        $workout1 = Workout::factory()->create(['user_id' => $user->id]);
        $workout1->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry1 = $workout1->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $workout2 = Workout::factory()->create(['user_id' => $user->id]);
        $workout2->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout2->id}/entries/{$entry1->id}", [
            'set_order' => 0,
        ])->assertStatus(404);
    }

    // Auth

    public function test_cannot_create_entry_on_other_users_workout(): void
    {
        $owner    = User::factory()->create();
        $creator  = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $creator->id]);
        $workout  = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($creator);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ])->assertStatus(403);
    }

    public function test_cannot_log_entry_against_another_users_exercise(): void
    {
        $owner           = User::factory()->create();
        $foreignExercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id]);

        $actor = User::factory()->create();
        Passport::actingAs($actor);
        $workout = Workout::factory()->create(['user_id' => $actor->id]);

        $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id' => $foreignExercise->id,
            'set_order'   => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');
    }

    // Group Fields

    public function test_creates_entry_with_group_assignment(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/entries", [
            'exercise_id'    => $exercise->id,
            'set_order'      => 0,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.entry_group_id', $group->id)
            ->assertJsonPath('data.group_round', 1);

        $this->assertDatabaseHas('workout_entries', [
            'workout_id'     => $workout->id,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);
    }

    public function test_updates_entry_group_assignment(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'entry_group_id' => $group->id,
            'group_round'    => 2,
        ])->assertOk()
            ->assertJsonPath('data.entry_group_id', $group->id)
            ->assertJsonPath('data.group_round', 2);
    }

    public function test_removes_entry_from_group_via_update(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $entry = $workout->entries()->create([
            'exercise_id'    => $exercise->id,
            'set_order'      => 0,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/entries/{$entry->id}", [
            'entry_group_id' => null,
            'group_round'    => null,
        ])->assertOk()
            ->assertJsonPath('data.entry_group_id', null)
            ->assertJsonPath('data.group_round', null);
    }
}
