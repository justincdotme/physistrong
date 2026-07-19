<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkoutExerciseTest extends TestCase
{
    use RefreshDatabase;

    // Attach

    public function test_attaches_exercise_to_workout(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench Press']);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.exercises.0.id', $exercise->id)
            ->assertJsonPath('data.exercises.0.name', 'Bench Press');

        $this->assertDatabaseHas('exercise_workout', [
            'workout_id'     => $workout->id,
            'exercise_id'    => $exercise->id,
            'exercise_order' => 0,
        ]);
    }

    public function test_rejects_duplicate_exercise_attachment(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(409);
    }

    public function test_auto_assigns_exercise_order(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 3']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise1->id,
        ])->assertStatus(201);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise2->id,
        ])->assertStatus(201);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise3->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise1->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise2->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise3->id,
            'exercise_order' => 2,
        ]);
    }

    public function test_validates_exercise_belongs_to_user_or_system(): void
    {
        $user          = User::factory()->create();
        $otherUser     = User::factory()->create();
        $otherExercise = Exercise::factory()->resistance()->create(['user_id' => $otherUser->id]);
        $workout       = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $otherExercise->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');

        $systemExercise = Exercise::factory()->resistance()->create(['name' => 'System Exercise']);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $systemExercise->id,
        ])->assertStatus(201);
    }

    // Detach

    public function test_detaches_exercise_from_workout(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exercise_workout', [
            'workout_id'  => $workout->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    public function test_detach_cascades_entries_and_metrics(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout  = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $metric = $entry->loadMetric()->create([
            'target_weight' => 100,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exercise_workout', [
            'exercise_id' => $exercise->id,
        ]);

        $this->assertDatabaseMissing('workout_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('log_load_metrics', ['id' => $metric->id]);

        $this->assertDatabaseHas('workouts', ['id' => $workout->id]);
    }

    // Reorder

    public function test_reorders_exercises(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 3']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);

        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $workout->exercises()->attach($exercise3->id, ['exercise_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [$exercise3->id, $exercise1->id, $exercise2->id],
        ])->assertOk();

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise3->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise1->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id'    => $exercise2->id,
            'exercise_order' => 2,
        ]);
    }

    public function test_reorder_rejects_nonexistent_exercise_id(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [999999, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_exercise_attached_to_other_workout(): void
    {
        $user         = User::factory()->create();
        $exercise1    = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2    = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $unattached   = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Elsewhere']);
        $workout      = Workout::factory()->create(['user_id' => $user->id]);
        $otherWorkout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $otherWorkout->exercises()->attach($unattached->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [$unattached->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_duplicate_exercise_ids(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [$exercise1->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_partial_exercise_id_list(): void
    {
        $user      = User::factory()->create();
        $exercise1 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Exercise 3']);
        $workout   = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $workout->exercises()->attach($exercise3->id, ['exercise_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [$exercise3->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids');

        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
        $this->assertDatabaseHas('exercise_workout', ['exercise_id' => $exercise3->id, 'exercise_order' => 2]);
    }

    // Authorization

    public function test_cannot_attach_to_other_users_workout(): void
    {
        $owner    = User::factory()->create();
        $attacher = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $attacher->id]);
        $workout  = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($attacher);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(403);
    }

    public function test_cannot_detach_from_other_users_workout(): void
    {
        $owner    = User::factory()->create();
        $detacher = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id]);
        $workout  = Workout::factory()->create(['user_id' => $owner->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($detacher);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$exercise->id}")
            ->assertStatus(403);
    }
}
