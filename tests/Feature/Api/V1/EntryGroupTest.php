<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EntryGroup;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EntryGroupTest extends TestCase
{
    use RefreshDatabase;

    private function createExercise(
        ?User $user,
        string $type = 'resistance',
        array $overrides = [],
    ): Exercise {
        $exercise = Exercise::create(array_merge([
            'name' => 'Test Exercise',
            'type' => $type,
            'user_id' => $user?->id,
        ], $overrides));

        $defaults = match ($type) {
            'resistance' => [],
            'timed_hold' => [],
            'distance' => [],
            'interval' => [],
            default => [],
        };

        $childRelation = match ($type) {
            'resistance' => 'resistance',
            'timed_hold' => 'timedHold',
            'distance' => 'distance',
            'interval' => 'interval',
            default => 'resistance',
        };

        $exercise->$childRelation()->create($defaults);

        return $exercise;
    }

    // -- Store --

    public function test_creates_entry_group(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'name' => 'Chest/Back Superset',
            'planned_rounds' => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds' => 60,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('entry_groups', [
            'workout_id' => $workout->id,
            'name' => 'Chest/Back Superset',
            'planned_rounds' => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds' => 60,
        ]);
    }

    public function test_creates_entry_group_with_defaults(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", []);

        $response->assertStatus(201);

        $this->assertDatabaseHas('entry_groups', [
            'workout_id' => $workout->id,
            'name' => null,
            'planned_rounds' => 1,
            'rest_between_exercises_seconds' => 0,
            'rest_between_rounds_seconds' => null,
        ]);
    }

    public function test_store_returns_workout_with_groups(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'name' => 'Push Circuit',
            'planned_rounds' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.groups.0.name', 'Push Circuit')
            ->assertJsonPath('data.groups.0.planned_rounds', 2);
    }

    public function test_cannot_create_group_on_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($other);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'planned_rounds' => 2,
        ])->assertStatus(403);
    }

    public function test_validates_planned_rounds_minimum(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups", [
            'planned_rounds' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('planned_rounds');
    }

    // -- Update --

    public function test_updates_entry_group(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'name' => 'Old Name',
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}", [
            'name' => 'New Name',
            'planned_rounds' => 4,
            'rest_between_exercises_seconds' => 45,
            'rest_between_rounds_seconds' => 90,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.groups.0.name', 'New Name')
            ->assertJsonPath('data.groups.0.planned_rounds', 4)
            ->assertJsonPath('data.groups.0.rest_between_exercises_seconds', 45)
            ->assertJsonPath('data.groups.0.rest_between_rounds_seconds', 90);
    }

    public function test_cannot_update_group_on_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $owner->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($other);

        $this->putJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}", [
            'name' => 'Hacked',
        ])->assertStatus(403);
    }

    public function test_update_returns_404_for_group_from_other_workout(): void
    {
        $user = User::factory()->create();
        $workout1 = Workout::factory()->create(['user_id' => $user->id]);
        $workout2 = Workout::factory()->create(['user_id' => $user->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout1->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout2->id}/groups/{$group->id}", [
            'name' => 'Cross-workout',
        ])->assertStatus(404);
    }

    // -- Destroy --

    public function test_deletes_entry_group(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
    }

    public function test_delete_group_preserves_entries_with_null_fk(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
            'entry_group_id' => $group->id,
            'group_round' => 1,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('workout_entries', [
            'id' => $entry->id,
            'entry_group_id' => null,
            'group_round' => null,
        ]);
    }

    public function test_workout_delete_cascades_groups(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entry_groups', ['id' => $group->id]);
    }

    // -- Assign Entries --

    public function test_assigns_entries_to_group(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Bench']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Row']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        $entry1 = $workout->entries()->create(['exercise_id' => $exercise1->id, 'set_order' => 0]);
        $entry2 = $workout->entries()->create(['exercise_id' => $exercise2->id, 'set_order' => 1]);

        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
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
            'id' => $entry1->id,
            'entry_group_id' => $group->id,
            'group_round' => 1,
        ]);

        $this->assertDatabaseHas('workout_entries', [
            'id' => $entry2->id,
            'entry_group_id' => $group->id,
            'group_round' => 1,
        ]);
    }

    public function test_assign_validates_entries_required(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('entries');
    }

    // -- Remove Entry --

    public function test_removes_entry_from_group(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
            'entry_group_id' => $group->id,
            'group_round' => 1,
        ]);

        Passport::actingAs($user);

        $response = $this->deleteJson(
            "/api/v1/workouts/{$workout->id}/groups/{$group->id}/entries/{$entry->id}"
        );

        $response->assertOk();

        $this->assertDatabaseHas('workout_entries', [
            'id' => $entry->id,
            'entry_group_id' => null,
            'group_round' => null,
        ]);
    }

    // -- Response Integration --

    public function test_workout_show_includes_groups(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        EntryGroup::create([
            'workout_id' => $workout->id,
            'name' => 'Push/Pull',
            'planned_rounds' => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds' => 60,
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
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = EntryGroup::create([
            'workout_id' => $workout->id,
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
            'entry_group_id' => $group->id,
            'group_round' => 1,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.entries.0.entry_group_id', $group->id)
            ->assertJsonPath('data.entries.0.group_round', 1);
    }

    public function test_standalone_entry_has_null_group_fields(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.entries.0.entry_group_id', null)
            ->assertJsonPath('data.entries.0.group_round', null);
    }
}
