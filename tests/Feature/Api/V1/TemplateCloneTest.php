<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\EntryGroup;

class TemplateCloneTest extends TestCase
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
            'distance' => ['distance_unit' => 'meters'],
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

    // -- Clone --

    public function test_clones_template_to_workout(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $exercise2 = $this->createExercise($user, 'timed_hold', ['name' => 'Plank']);

        $template = WorkoutTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Push Day',
        ]);

        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Push Day')
            ->assertJsonPath('data.date', '2026-07-01');

        $workoutId = $response->json('data.id');

        $this->assertDatabaseHas('workouts', [
            'id' => $workoutId,
            'name' => 'Push Day',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise1->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise2->id,
            'exercise_order' => 1,
        ]);
    }

    public function test_clone_creates_one_entry_per_exercise(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Squat']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Deadlift']);

        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $workoutId = $response->json('data.id');

        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise1->id,
            'set_order' => 0,
        ]);

        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise2->id,
            'set_order' => 1,
        ]);
    }

    public function test_clone_allows_name_override(): void
    {
        $user = User::factory()->create();
        $template = WorkoutTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template Name',
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
            'name' => 'Custom Workout Name',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Custom Workout Name');
    }

    public function test_cloned_workout_is_independent_of_template(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');

        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $workoutId = $response->json('data.id');

        $this->deleteJson("/api/v1/templates/{$template->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('workouts', ['id' => $workoutId]);
        $this->assertDatabaseHas('exercise_workout', ['workout_id' => $workoutId]);
        $this->assertDatabaseHas('workout_entries', ['workout_id' => $workoutId]);
    }

    public function test_clone_validates_date_required(): void
    {
        $user = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/templates/{$template->id}/clone", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_cannot_clone_other_users_template(): void
    {
        $owner = User::factory()->create();
        $cloner = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($cloner);

        $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ])->assertStatus(403);
    }

    public function test_clone_creates_entry_groups_from_template_groups(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Barbell Row']);

        $template = WorkoutTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Push/Pull',
        ]);

        $templateGroup = $template->groups()->create([
            'name' => 'Chest/Back Superset',
            'planned_rounds' => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds' => 60,
        ]);

        $template->exercises()->attach($exercise1->id, [
            'exercise_order' => 0,
            'template_entry_group_id' => $templateGroup->id,
        ]);
        $template->exercises()->attach($exercise2->id, [
            'exercise_order' => 1,
            'template_entry_group_id' => $templateGroup->id,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201);

        $workoutId = $response->json('data.id');

        $this->assertDatabaseHas('entry_groups', [
            'workout_id' => $workoutId,
            'name' => 'Chest/Back Superset',
            'planned_rounds' => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds' => 60,
        ]);
    }

    public function test_clone_expands_grouped_exercises_into_round_entries(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Barbell Row']);

        $template = WorkoutTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $templateGroup = $template->groups()->create([
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        $template->exercises()->attach($exercise1->id, [
            'exercise_order' => 0,
            'template_entry_group_id' => $templateGroup->id,
        ]);
        $template->exercises()->attach($exercise2->id, [
            'exercise_order' => 1,
            'template_entry_group_id' => $templateGroup->id,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $workoutId = $response->json('data.id');
        $workoutGroupId = EntryGroup::where('workout_id', $workoutId)->first()->id;

        // 2 exercises x 2 rounds = 4 entries
        $this->assertDatabaseCount('workout_entries', 4);

        // Round 1
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise1->id,
            'set_order' => 0,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 1,
        ]);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise2->id,
            'set_order' => 1,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 1,
        ]);

        // Round 2
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise1->id,
            'set_order' => 2,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 2,
        ]);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $exercise2->id,
            'set_order' => 3,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 2,
        ]);
    }

    public function test_clone_interleaves_standalone_and_grouped_entries(): void
    {
        $user = User::factory()->create();
        $squat = $this->createExercise($user, 'resistance', ['name' => 'Squat']);
        $bench = $this->createExercise($user, 'resistance', ['name' => 'Bench']);
        $row = $this->createExercise($user, 'resistance', ['name' => 'Row']);
        $plank = $this->createExercise($user, 'timed_hold', ['name' => 'Plank']);

        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        $templateGroup = $template->groups()->create([
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        // Squat standalone, then Bench/Row superset, then Plank standalone
        $template->exercises()->attach($squat->id, ['exercise_order' => 0]);
        $template->exercises()->attach($bench->id, [
            'exercise_order' => 1,
            'template_entry_group_id' => $templateGroup->id,
        ]);
        $template->exercises()->attach($row->id, [
            'exercise_order' => 2,
            'template_entry_group_id' => $templateGroup->id,
        ]);
        $template->exercises()->attach($plank->id, ['exercise_order' => 3]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $workoutId = $response->json('data.id');
        $workoutGroupId = EntryGroup::where('workout_id', $workoutId)->first()->id;

        // 2 standalone + (2 exercises x 2 rounds) = 6 entries
        $this->assertDatabaseCount('workout_entries', 6);

        // set_order 0: Squat (standalone)
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $squat->id,
            'set_order' => 0,
            'entry_group_id' => null,
        ]);

        // set_order 1-4: Bench/Row superset rounds 1-2
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $bench->id,
            'set_order' => 1,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 1,
        ]);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $row->id,
            'set_order' => 2,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 1,
        ]);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $bench->id,
            'set_order' => 3,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 2,
        ]);
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $row->id,
            'set_order' => 4,
            'entry_group_id' => $workoutGroupId,
            'group_round' => 2,
        ]);

        // set_order 5: Plank (standalone)
        $this->assertDatabaseHas('workout_entries', [
            'workout_id' => $workoutId,
            'exercise_id' => $plank->id,
            'set_order' => 5,
            'entry_group_id' => null,
        ]);
    }

    public function test_clone_response_includes_groups(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench']);

        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $templateGroup = $template->groups()->create([
            'name' => 'Test Group',
            'planned_rounds' => 2,
            'rest_between_exercises_seconds' => 30,
        ]);
        $template->exercises()->attach($exercise->id, [
            'exercise_order' => 0,
            'template_entry_group_id' => $templateGroup->id,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/clone", [
            'date' => '2026-07-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.groups.0.name', 'Test Group')
            ->assertJsonPath('data.groups.0.planned_rounds', 2)
            ->assertJsonPath('data.groups.0.rest_between_exercises_seconds', 30);
    }
}
