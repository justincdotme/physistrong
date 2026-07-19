<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EntryGroup;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EntryGroupTest extends TestCase
{
    use RefreshDatabase;

    // Store

    public function test_creates_entry_group(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'name'                           => 'Chest/Back Superset',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 60,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('entry_groups', [
            'workout_id'                     => $workout->id,
            'name'                           => 'Chest/Back Superset',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 60,
        ]);
    }

    public function test_creates_entry_group_with_defaults(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", []);

        $response->assertStatus(201);

        $this->assertDatabaseHas('entry_groups', [
            'workout_id'                     => $workout->id,
            'name'                           => null,
            'planned_rounds'                 => 1,
            'rest_between_exercises_seconds' => 0,
            'rest_between_rounds_seconds'    => null,
        ]);
    }

    public function test_store_returns_workout_with_groups(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'name'           => 'Push Circuit',
            'planned_rounds' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.groups.0.name', 'Push Circuit')
            ->assertJsonPath('data.groups.0.planned_rounds', 2);
    }

    public function test_cannot_create_group_on_other_users_workout(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($other);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'planned_rounds' => 2,
        ])->assertStatus(403);
    }

    public function test_validates_planned_rounds_minimum(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'planned_rounds' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('planned_rounds');
    }

    // Destroy

    public function test_deletes_entry_group(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group   = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
    }

    public function test_destroy_returns_404_for_group_from_other_workout(): void
    {
        $user     = User::factory()->create();
        $workout1 = Workout::factory()->create(['user_id' => $user->id]);
        $workout2 = Workout::factory()->create(['user_id' => $user->id]);
        $group    = EntryGroup::create([
            'workout_id'                     => $workout1->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout2->id}/groups/{$group->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('entry_groups', ['id' => $group->id]);
    }

    public function test_delete_group_preserves_entries_with_null_fk(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
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

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('workout_entries', [
            'id'             => $entry->id,
            'entry_group_id' => null,
            'group_round'    => null,
        ]);
    }

    public function test_workout_delete_cascades_groups(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group   = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
    }

    // Assign Entries

    public function test_assigns_entries_to_group(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Row']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise1->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise2->id, 'set_order' => 1]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry1->id, 'group_round' => 1],
                ['entry_id' => $entry2->id, 'group_round' => 1],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('workout_entries', [
            'id'             => $entry1->id,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        $this->assertDatabaseHas('workout_entries', [
            'id'             => $entry2->id,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);
    }

    public function test_assign_validates_entries_required(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group   = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('entries');
    }

    public function test_assign_rejects_nonexistent_entry_id(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => 999999, 'group_round' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.entry_id');
    }

    public function test_assign_rejects_entry_from_other_workout(): void
    {
        $user         = User::factory()->create();
        $exercise     = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout      = Workout::factory()->create(['user_id' => $user->id]);
        $otherWorkout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $otherWorkout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $foreignEntry = $otherWorkout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $foreignEntry->id, 'group_round' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.entry_id');

        $this->assertDatabaseHas('workout_entries', [
            'id'             => $foreignEntry->id,
            'entry_group_id' => null,
        ]);
    }

    public function test_assign_rejects_duplicate_entry_ids(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $entry = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry->id, 'group_round' => 1],
                ['entry_id' => $entry->id, 'group_round' => 2],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.entry_id');

        $this->assertDatabaseHas('workout_entries', ['id' => $entry->id, 'entry_group_id' => null]);
    }

    public function test_assign_allows_partial_subset_of_entries(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 1]);
        $entry3 = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 2]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry1->id, 'group_round' => 1],
                ['entry_id' => $entry2->id, 'group_round' => 1],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('workout_entries', ['id' => $entry1->id, 'entry_group_id' => $group->id]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry2->id, 'entry_group_id' => $group->id]);
        $this->assertDatabaseHas('workout_entries', ['id' => $entry3->id, 'entry_group_id' => null]);
    }

    // Response Integration

    public function test_workout_show_includes_groups(): void
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        EntryGroup::create([
            'workout_id'                     => $workout->id,
            'name'                           => 'Push/Pull',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 60,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.groups.0.name', 'Push/Pull')
            ->assertJsonPath('data.groups.0.planned_rounds', 3)
            ->assertJsonPath('data.groups.0.rest_between_exercises_seconds', 30)
            ->assertJsonPath('data.groups.0.rest_between_rounds_seconds', 60);
    }

    public function test_entry_response_includes_group_fields(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $workout->entries()->create([
            'exercise_id'    => $exercise->id,
            'set_order'      => 0,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.entries.0.entry_group_id', $group->id)
            ->assertJsonPath('data.entries.0.group_round', 1);
    }

    public function test_standalone_entry_has_null_group_fields(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.entries.0.entry_group_id', null)
            ->assertJsonPath('data.entries.0.group_round', null);
    }

    // Assign Entries: Round Expansion

    public function test_assign_entries_expands_rounds(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise1->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise2->id, 'set_order' => 1]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry1->id, 'group_round' => 1],
                ['entry_id' => $entry2->id, 'group_round' => 1],
            ],
        ])->assertOk();

        $entries = WorkoutEntry::where('entry_group_id', $group->id)->get();

        $this->assertCount(6, $entries);
        $this->assertCount(2, $entries->where('group_round', 1));
        $this->assertCount(2, $entries->where('group_round', 2));
        $this->assertCount(2, $entries->where('group_round', 3));

        // Round 2 and 3 mirror round 1's exercises
        $round1Exercises = $entries->where('group_round', 1)->pluck('exercise_id')->sort()->values();
        $round2Exercises = $entries->where('group_round', 2)->pluck('exercise_id')->sort()->values();
        $round3Exercises = $entries->where('group_round', 3)->pluck('exercise_id')->sort()->values();
        $this->assertEquals($round1Exercises, $round2Exercises);
        $this->assertEquals($round1Exercises, $round3Exercises);

        // set_order values are unique and expanded rounds append after round 1
        $setOrders = $entries->pluck('set_order')->sort()->values()->all();
        $this->assertCount(6, array_unique($setOrders));
        $maxRound1SetOrder = $entries->where('group_round', 1)->max('set_order');
        $minRound2SetOrder = $entries->where('group_round', 2)->min('set_order');
        $this->assertGreaterThan($maxRound1SetOrder, $minRound2SetOrder);
    }

    public function test_assign_entries_clones_target_metrics_without_actuals_or_intensity(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $entry->repMetric()->create([
            'target_reps' => 10,
            'actual_reps' => 8,
            'to_failure'  => true,
        ]);
        $entry->intensityMetric()->create([
            'rpe' => 9,
        ]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry->id, 'group_round' => 1],
            ],
        ])->assertOk();

        $round2Entry = WorkoutEntry::where('entry_group_id', $group->id)
            ->where('group_round', 2)
            ->first();

        $this->assertNotNull($round2Entry);

        $clonedReps = $round2Entry->repMetric;
        $this->assertNotNull($clonedReps);
        $this->assertEquals(10, $clonedReps->target_reps);
        $this->assertNull($clonedReps->actual_reps);
        $this->assertTrue($clonedReps->to_failure);

        $this->assertNull($round2Entry->intensityMetric);
    }

    public function test_assign_entries_is_idempotent(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise1->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise2->id, 'set_order' => 1]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $payload = [
            'entries' => [
                ['entry_id' => $entry1->id, 'group_round' => 1],
                ['entry_id' => $entry2->id, 'group_round' => 1],
            ],
        ];

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", $payload)->assertOk();
        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", $payload)->assertOk();

        $this->assertCount(6, WorkoutEntry::where('entry_group_id', $group->id)->get());
    }

    public function test_assign_entries_skips_expansion_for_single_round_group(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create(['exercise_id' => $exercise->id, 'set_order' => 0]);

        $group = EntryGroup::create([
            'workout_id'                     => $workout->id,
            'planned_rounds'                 => 1,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [
            'entries' => [
                ['entry_id' => $entry->id, 'group_round' => 1],
            ],
        ])->assertOk();

        $this->assertCount(1, WorkoutEntry::where('entry_group_id', $group->id)->get());
    }

    // Destroy: delete_entries mode

    public function test_destroy_with_delete_entries_removes_entries_and_detaches_exercise(): void
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

        $workout->entries()->create([
            'exercise_id'    => $exercise->id,
            'set_order'      => 0,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}?delete_entries=1")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('workout_entries', ['entry_group_id' => $group->id]);
        $this->assertDatabaseMissing('exercise_workout', [
            'exercise_id' => $exercise->id,
            'workout_id'  => $workout->id,
        ]);
    }

    public function test_destroy_with_delete_entries_keeps_exercise_with_standalone_entry(): void
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

        $groupedEntry = $workout->entries()->create([
            'exercise_id'    => $exercise->id,
            'set_order'      => 0,
            'entry_group_id' => $group->id,
            'group_round'    => 1,
        ]);

        $standaloneEntry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 1,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}?delete_entries=1")
            ->assertNoContent();

        $this->assertDatabaseMissing('workout_entries', ['id' => $groupedEntry->id]);
        $this->assertDatabaseHas('workout_entries', ['id' => $standaloneEntry->id]);
        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise->id,
            'workout_id'  => $workout->id,
        ]);
    }

    public function test_destroy_without_flag_preserves_entries(): void
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

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('workout_entries', [
            'id'             => $entry->id,
            'entry_group_id' => null,
            'group_round'    => null,
        ]);
    }
}
