<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Exercise;
use App\Models\TemplateEntryGroup;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TemplateEntryGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_template_group(): void
    {
        $user     = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/groups", [
            'name'                           => 'Push Superset',
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
            'rest_between_rounds_seconds'    => 60,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('template_entry_groups', [
            'template_id'    => $template->id,
            'name'           => 'Push Superset',
            'planned_rounds' => 3,
        ]);
    }

    public function test_store_returns_template_with_groups(): void
    {
        $user     = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson("/api/v1/templates/{$template->id}/groups", [
            'name'           => 'Circuit',
            'planned_rounds' => 4,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.groups.0.name', 'Circuit')
            ->assertJsonPath('data.groups.0.planned_rounds', 4);
    }

    public function test_cannot_create_group_on_other_users_template(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $owner->id]);

        Passport::actingAs($other);

        $this->postJson("/api/v1/templates/{$template->id}/groups", [
            'planned_rounds' => 2,
        ])->assertStatus(403);
    }

    public function test_deletes_template_group(): void
    {
        $user     = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $group    = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('template_entry_groups', ['id' => $group->id]);
    }

    public function test_delete_group_nulls_exercise_pivot_fk(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench Press']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $group    = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 0,
        ]);
        $template->exercises()->attach($exercise->id, [
            'exercise_order'          => 0,
            'template_entry_group_id' => $group->id,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template->id}/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $exercise->id,
            'template_entry_group_id' => null,
        ]);
    }

    public function test_destroy_returns_404_for_group_from_other_template(): void
    {
        $user      = User::factory()->create();
        $template1 = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template2 = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $group     = TemplateEntryGroup::create([
            'template_id'                    => $template1->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/templates/{$template2->id}/groups/{$group->id}")
            ->assertStatus(404);
    }

    public function test_assigns_exercises_to_group(): void
    {
        $user     = User::factory()->create();
        $ex1      = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $ex2      = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Row']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($ex1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($ex2->id, ['exercise_order' => 1]);

        $group = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $response = $this->postJson(
            "/api/v1/templates/{$template->id}/groups/{$group->id}/exercises",
            ['exercise_ids' => [$ex1->id, $ex2->id]],
        );

        $response->assertOk();

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $ex1->id,
            'template_entry_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $ex2->id,
            'template_entry_group_id' => $group->id,
        ]);
    }

    public function test_assign_exercises_rejects_nonexistent_exercise_id(): void
    {
        $user     = User::factory()->create();
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $group    = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson(
            "/api/v1/templates/{$template->id}/groups/{$group->id}/exercises",
            ['exercise_ids' => [999]],
        )->assertStatus(422)
            ->assertJsonValidationErrors('exercise_ids.0');
    }

    public function test_assign_exercises_rejects_exercise_from_another_template(): void
    {
        $user      = User::factory()->create();
        $exercise  = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $template1 = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template2 = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template1->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = TemplateEntryGroup::create([
            'template_id'                    => $template2->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson(
            "/api/v1/templates/{$template2->id}/groups/{$group->id}/exercises",
            ['exercise_ids' => [$exercise->id]],
        )->assertStatus(422)
            ->assertJsonValidationErrors('exercise_ids.0');
    }

    public function test_assign_exercises_rejects_duplicate_ids(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $group = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 2,
            'rest_between_exercises_seconds' => 0,
        ]);

        Passport::actingAs($user);

        $this->postJson(
            "/api/v1/templates/{$template->id}/groups/{$group->id}/exercises",
            ['exercise_ids' => [$exercise->id, $exercise->id]],
        )->assertStatus(422)
            ->assertJsonValidationErrors('exercise_ids.0');
    }

    public function test_assign_exercises_succeeds_with_valid_subset(): void
    {
        $user     = User::factory()->create();
        $ex1      = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Bench']);
        $ex2      = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Row']);
        $ex3      = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Curl']);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($ex1->id, ['exercise_order' => 0]);
        $template->exercises()->attach($ex2->id, ['exercise_order' => 1]);
        $template->exercises()->attach($ex3->id, ['exercise_order' => 2]);

        $group = TemplateEntryGroup::create([
            'template_id'                    => $template->id,
            'planned_rounds'                 => 3,
            'rest_between_exercises_seconds' => 30,
        ]);

        Passport::actingAs($user);

        $this->postJson(
            "/api/v1/templates/{$template->id}/groups/{$group->id}/exercises",
            ['exercise_ids' => [$ex1->id, $ex3->id]],
        )->assertOk();

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $ex1->id,
            'template_entry_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $ex3->id,
            'template_entry_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('template_exercises', [
            'template_id'             => $template->id,
            'exercise_id'             => $ex2->id,
            'template_entry_group_id' => null,
        ]);
    }
}
