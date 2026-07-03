<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkoutTemplateTest extends TestCase
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

    public function test_creates_template(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/templates', [
            'name' => 'Push Day',
            'notes' => 'Chest and shoulders',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Push Day')
            ->assertJsonPath('data.notes', 'Chest and shoulders');

        $this->assertDatabaseHas('workout_templates', [
            'name' => 'Push Day',
            'user_id' => $user->id,
        ]);
    }

    public function test_creates_template_without_notes(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/templates', [
            'name' => 'Leg Day',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Leg Day')
            ->assertJsonPath('data.notes', null);
    }

    public function test_validates_required_fields_for_store(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/templates', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // -- Index --

    public function test_lists_own_templates(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        WorkoutTemplate::factory(3)->create(['user_id' => $user->id]);
        WorkoutTemplate::factory(1)->create(['user_id' => $other->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/templates');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // -- Show --

    public function test_shows_template_with_exercises(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Bench Press']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.exercises.0.id', $exercise->id)
            ->assertJsonPath('data.exercises.0.name', 'Bench Press')
            ->assertJsonPath('data.exercises.0.exercise_order', 0);
    }

    public function test_cannot_view_other_users_template(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($viewer);

        $this->getJson("/api/v1/templates/{$template->id}")
            ->assertStatus(403);
    }

    // -- Update --

    public function test_updates_template(): void
    {
        $user = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        Passport::actingAs($user);

        $this->putJson("/api/v1/templates/{$template->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_cannot_update_other_users_template(): void
    {
        $owner = User::factory()->create();
        $updater = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($updater);

        $this->putJson("/api/v1/templates/{$template->id}", ['name' => 'Hacked'])
            ->assertStatus(403);
    }

    // -- Destroy --

    public function test_deletes_template(): void
    {
        $user = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workout_templates', ['id' => $template->id]);
    }

    public function test_delete_cascades_exercises_and_groups(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance');
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        $group = $template->groups()->create([
            'name' => 'Superset',
            'planned_rounds' => 3,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workout_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('template_exercises', ['template_id' => $template->id]);
        $this->assertDatabaseMissing('template_entry_groups', ['id' => $group->id]);
    }

    // -- Auth --

    public function test_unauthenticated_cannot_access_templates(): void
    {
        $this->getJson('/api/v1/templates')
            ->assertStatus(401);

        $this->postJson('/api/v1/templates', ['name' => 'Nope'])
            ->assertStatus(401);
    }
}
