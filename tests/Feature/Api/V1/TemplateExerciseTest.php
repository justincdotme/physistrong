<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TemplateExerciseTest extends TestCase
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

    public function test_attaches_exercise_to_template(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.exercises.0.id', $exercise->id)
            ->assertJsonPath('data.exercises.0.name', 'Bench Press');

        $this->assertDatabaseHas('template_exercises', [
            'template_id' => $template->id,
            'exercise_id' => $exercise->id,
            'exercise_order' => 0,
        ]);
    }

    public function test_rejects_duplicate_exercise_attachment(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(409);
    }

    public function test_auto_assigns_exercise_order(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $exercise3 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 3']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise1->id,
        ])->assertStatus(201);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise2->id,
        ])->assertStatus(201);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise3->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise1->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise2->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise3->id,
            'exercise_order' => 2,
        ]);
    }

    public function test_validates_exercise_belongs_to_user_or_system(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherExercise = $this->createExercise($otherUser, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $otherExercise->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');

        $systemExercise = $this->createExercise(null, 'resistance', ['name' => 'System Exercise']);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $systemExercise->id,
        ])->assertStatus(201);
    }

    // -- Detach --

    public function test_detaches_exercise_from_template(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template->id}/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('template_exercises', [
            'template_id' => $template->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    // -- Reorder --

    public function test_reorders_exercises(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $exercise3 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 3']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $template->exercises()->attach($exercise3->id, ['exercise_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}/exercises/reorder", [
            'ids' => [$exercise3->id, $exercise1->id, $exercise2->id],
        ])->assertOk();

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise3->id,
            'exercise_order' => 0,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise1->id,
            'exercise_order' => 1,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'exercise_id' => $exercise2->id,
            'exercise_order' => 2,
        ]);
    }

    public function test_reorder_rejects_nonexistent_exercise_id(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}/exercises/reorder", [
            'ids' => [999999, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_exercise_attached_to_other_template(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $unattached = $this->createExercise($user, 'resistance', ['name' => 'Elsewhere']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $otherTemplate = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $otherTemplate->exercises()->attach($unattached->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}/exercises/reorder", [
            'ids' => [$unattached->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_duplicate_exercise_ids(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}/exercises/reorder", [
            'ids' => [$exercise1->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');

        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
    }

    public function test_reorder_rejects_partial_exercise_id_list(): void
    {
        $user = User::factory()->create();
        $exercise1 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 1']);
        $exercise2 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 2']);
        $exercise3 = $this->createExercise($user, 'resistance', ['name' => 'Exercise 3']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($exercise2->id, ['exercise_order' => 1]);
        $template->exercises()->attach($exercise3->id, ['exercise_order' => 2]);

        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}/exercises/reorder", [
            'ids' => [$exercise3->id, $exercise1->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids');

        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise1->id, 'exercise_order' => 0]);
        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise2->id, 'exercise_order' => 1]);
        $this->assertDatabaseHas('template_exercises', ['exercise_id' => $exercise3->id, 'exercise_order' => 2]);
    }

    // -- Authorization --

    public function test_cannot_attach_to_other_users_template(): void
    {
        $owner = User::factory()->create();
        $attacher = User::factory()->create();
        $exercise = $this->createExercise($attacher, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($attacher);

        $this->postJson("/api/v1/templates/{$template->id}/exercises", [
            'exercise_id' => $exercise->id,
        ])->assertStatus(403);
    }

    public function test_cannot_detach_from_other_users_template(): void
    {
        $owner = User::factory()->create();
        $detacher = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($detacher);

        $this->deleteJson("/api/v1/templates/{$template->id}/exercises/{$exercise->id}")
            ->assertStatus(403);
    }
}
