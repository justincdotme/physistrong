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

    // -- Attach --

    public function test_attaches_exercise_to_workout(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.exercises.0.id', $exercise->id)
            ->assertJsonPath('data.exercises.0.name', 'Bench Press');

        $this->assertDatabaseHas('exercise_workout', [
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'exercise_order' => 0,
        ]);
    }

    public function test_rejects_duplicate_exercise_attachment(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(409);
    }

    public function test_auto_assigns_exercise_order(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $exercise3 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 3']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);

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
            'exercise_id' => $exercise1->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise2->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise3->id,
            'exercise_order' => 2,
        ]);
    }

    public function test_validates_exercise_belongs_to_user_or_system(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherExercise = $this->createExercise($otherUser, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $otherExercise->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');

        $systemExercise = $this->createExercise(null, 'resistance', ['name' => 'System Exercise']);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $systemExercise->id,
        ])->assertStatus(201);
    }

    // -- Detach --

    public function test_detaches_exercise_from_workout(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exercise_workout', [
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    public function test_detach_cascades_entries_and_metrics(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
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

    // -- Reorder --

    public function test_reorders_exercises(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $exercise3 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 3']);
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        $workout->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $workout->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $workout->exercises()->attach($exercise3->id, ['exercise_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}/exercises/reorder", [
            'ids' => [$exercise3->id, $exercise1->id, $exercise2->id],
        ])->assertOk();

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise3->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise1->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('exercise_workout', [
            'exercise_id' => $exercise2->id,
            'exercise_order' => 2,
        ]);
    }

    // -- Authorization --

    public function test_cannot_attach_to_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $attacher = User::factory()->create();
        $exercise = $this->createExercise($attacher, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($attacher);

        $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(403);
    }

    public function test_cannot_detach_from_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $detacher = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance');
        $workout = Workout::factory()->create(['user_id' => $owner->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($detacher);

        $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$exercise->id}")
            ->assertStatus(403);
    }
}
