<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    // Store

    public function test_creates_workout(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/workouts', [
            'name' => 'Leg Day',
            'date' => '2026-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Leg Day')
            ->assertJsonPath('data.date', '2026-01-15');

        $this->assertDatabaseHas('workouts', [
            'name' => 'Leg Day',
            'user_id' => $user->id,
        ]);
    }

    public function test_validates_required_fields_for_store(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/workouts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'date']);
    }

    public function test_validates_exhaustion_range(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/workouts', [
            'name' => 'Test',
            'date' => '2026-01-15',
            'exhaustion' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exhaustion');

        $this->postJson('/api/v1/workouts', [
            'name' => 'Test',
            'date' => '2026-01-15',
            'exhaustion' => 11,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('exhaustion');

        $this->postJson('/api/v1/workouts', [
            'name' => 'Test',
            'date' => '2026-01-15',
            'exhaustion' => 1,
        ])->assertStatus(201);

        $this->postJson('/api/v1/workouts', [
            'name' => 'Test 2',
            'date' => '2026-01-15',
            'exhaustion' => 10,
        ])->assertStatus(201);
    }

    // Index

    public function test_lists_own_workouts_paginated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Workout::factory(3)->create(['user_id' => $user->id]);
        Workout::factory(1)->create(['user_id' => $other->id]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/workouts');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 3);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_lists_workouts_with_entry_counts(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $completedEntry1 = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);
        $completedEntry1->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 95,
        ]);

        $completedEntry2 = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 1,
        ]);
        $completedEntry2->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 90,
        ]);

        $incompleteEntry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 2,
        ]);
        $incompleteEntry->loadMetric()->create([
            'target_weight' => 100,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/workouts');

        $response->assertOk()
            ->assertJsonPath('data.0.entries_count', 3)
            ->assertJsonPath('data.0.completed_entries_count', 2);
    }

    // Show

    public function test_shows_workout_with_exercises_and_entries(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);

        $entry->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 95,
        ]);

        $entry->repMetric()->create([
            'target_reps' => 10,
            'actual_reps' => 8,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $workout->id)
            ->assertJsonPath('data.exercises.0.exercise_order', 0)
            ->assertJsonPath('data.entries.0.metrics.load.target_weight', '100.00')
            ->assertJsonPath('data.entries.0.metrics.reps.target_reps', 10);
    }

    public function test_cannot_view_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($viewer);

        $this->getJson("/api/v1/workouts/{$workout->id}")
            ->assertStatus(403);
    }

    // Update

    public function test_updates_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        Passport::actingAs($user);

        $this->putJson("/api/v1/workouts/{$workout->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_cannot_update_other_users_workout(): void
    {
        $owner = User::factory()->create();
        $updater = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($updater);

        $this->putJson("/api/v1/workouts/{$workout->id}", ['name' => 'Hacked'])
            ->assertStatus(403);
    }

    // Destroy

    public function test_deletes_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workouts', ['id' => $workout->id]);
    }

    public function test_delete_cascades_entries_and_metrics(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);

        $metric = $entry->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 95,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/workouts/{$workout->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workouts', ['id' => $workout->id]);
        $this->assertDatabaseMissing('workout_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('log_load_metrics', ['id' => $metric->id]);
    }

    // Auth

    public function test_unauthenticated_cannot_access_workouts(): void
    {
        $this->getJson('/api/v1/workouts')
            ->assertStatus(401);

        $this->postJson('/api/v1/workouts', ['name' => 'Nope', 'date' => '2026-01-15'])
            ->assertStatus(401);
    }

    public function test_metric_response_contains_only_declared_columns(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $entry = $workout->entries()->create([
            'exercise_id' => $exercise->id,
            'set_order' => 0,
        ]);

        $entry->loadMetric()->create([
            'target_weight' => 100,
            'actual_weight' => 95,
            'bodyweight_only' => false,
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");
        $loadMetric = $response->json('data.entries.0.metrics.load');

        $this->assertEqualsCanonicalizing(
            ['target_weight', 'actual_weight', 'bodyweight_only'],
            array_keys($loadMetric),
        );
    }
}
