<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

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
}
