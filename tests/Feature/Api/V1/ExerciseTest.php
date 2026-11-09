<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EquipmentType;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    // Store

    /** @return array<string, array<mixed>> */
    public static function exerciseTypeProvider(): array
    {
        return [
            'resistance' => [
                'resistance',
                ['bodyweight_base' => true, 'allows_added_weight' => true, 'bilateral' => false],
            ],
            'timed_hold' => [
                'timed_hold',
                ['target_duration_seconds' => 60],
            ],
            'distance' => [
                'distance',
                ['tracks_elevation' => true],
            ],
            'interval' => [
                'interval',
                ['default_work_seconds' => 30, 'default_rest_seconds' => 15, 'default_rounds' => 8],
            ],
        ];
    }

    // Index

    public function test_lists_system_and_own_exercises(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Exercise::factory()->resistance()->create(['name' => 'System Bench Press']);
        Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'My Custom Exercise']);
        Exercise::factory()->resistance()->create(['user_id' => $other->id, 'name' => 'Other User Exercise']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises');

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('System Bench Press'));
        $this->assertTrue($names->contains('My Custom Exercise'));
        $this->assertFalse($names->contains('Other User Exercise'));
    }

    public function test_filters_exercises_by_type(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->resistance()->create(['name' => 'Bench Press']);
        Exercise::factory()->distance()->create(['name' => 'Treadmill Run']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises?type=resistance');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Bench Press'));
        $this->assertFalse($names->contains('Treadmill Run'));
    }

    public function test_filters_exercises_by_equipment_type(): void
    {
        $barbell  = EquipmentType::create(['name' => 'barbell', 'user_id' => null, 'is_system' => true]);
        $dumbbell = EquipmentType::create(['name' => 'dumbbell', 'user_id' => null, 'is_system' => true]);

        $user = User::factory()->create();
        Exercise::factory()->resistance()->create(['name' => 'Barbell Curl', 'equipment_type_id' => $barbell->id]);
        Exercise::factory()->resistance()->create(['name' => 'Dumbbell Curl', 'equipment_type_id' => $dumbbell->id]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises?equipment_type_id={$barbell->id}");

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Barbell Curl'));
        $this->assertFalse($names->contains('Dumbbell Curl'));
    }

    public function test_searches_exercises_by_name(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->resistance()->create(['name' => 'Bench Press']);
        Exercise::factory()->resistance()->create(['name' => 'Deadlift']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises?search=bench');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Bench Press'));
        $this->assertFalse($names->contains('Deadlift'));
    }

    public function test_rejects_garbage_exercise_type(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/v1/exercises?type=garbage')
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_rejects_empty_exercise_type(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/v1/exercises?type=')
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->resistance()->create(['name' => '100% Effort']);
        Exercise::factory()->resistance()->create(['name' => '100 Pushups']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises?search=100%25');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('100% Effort'));
        $this->assertFalse($names->contains('100 Pushups'));
    }

    // Show

    public function test_shows_exercise_with_type_attributes(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance([
            'bodyweight_base'     => true,
            'allows_added_weight' => true,
            'bilateral'           => true,
        ])->create(['name' => 'Pull-up']);

        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/exercises/{$exercise->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Pull-up')
            ->assertJsonPath('data.type', 'resistance')
            ->assertJsonPath('data.type_attributes.bodyweight_base', true)
            ->assertJsonPath('data.type_attributes.allows_added_weight', true)
            ->assertJsonPath('data.type_attributes.bilateral', true);
    }

    public function test_cannot_view_other_users_exercise(): void
    {
        $owner    = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id, 'name' => 'Secret Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    /** @param array<string, mixed> $typeAttributes */
    #[DataProvider('exerciseTypeProvider')]
    public function test_creates_exercise_for_each_cti_type(string $type, array $typeAttributes): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/exercises', [
            'name'            => "Test {$type} exercise",
            'type'            => $type,
            'type_attributes' => $typeAttributes,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', $type)
            ->assertJsonPath('data.name', "Test {$type} exercise")
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('exercises', [
            'name'    => "Test {$type} exercise",
            'type'    => $type,
            'user_id' => $user->id,
        ]);

        $exerciseId = $response->json('data.id');
        $childTable = match ($type) {
            'resistance' => 'exercise_resistance',
            'timed_hold' => 'exercise_timed_hold',
            'distance'   => 'exercise_distance',
            'interval'   => 'exercise_interval',
            default      => throw new InvalidArgumentException("Unexpected exercise type: {$type}"),
        };
        $this->assertDatabaseHas($childTable, ['exercise_id' => $exerciseId]);

        foreach ($typeAttributes as $key => $value) {
            $response->assertJsonPath("data.type_attributes.{$key}", $value);
        }
    }

    public function test_creates_resistance_with_defaults_when_no_attributes(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/exercises', [
            'name' => 'Basic Push-up',
            'type' => 'resistance',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type_attributes.bodyweight_base', false)
            ->assertJsonPath('data.type_attributes.allows_added_weight', false)
            ->assertJsonPath('data.type_attributes.bilateral', true);
    }

    public function test_creates_exercise_with_equipment_type(): void
    {
        $barbell = EquipmentType::create(['name' => 'barbell', 'user_id' => null, 'is_system' => true]);
        $user    = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/exercises', [
            'name'              => 'Barbell Squat',
            'type'              => 'resistance',
            'equipment_type_id' => $barbell->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.equipment_type_id', $barbell->id)
            ->assertJsonPath('data.equipment_type.name', 'barbell');
    }

    public function test_allows_same_name_as_system_exercise(): void
    {
        Exercise::factory()->resistance()->create(['name' => 'Bench Press']);
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/exercises', [
            'name' => 'Bench Press',
            'type' => 'resistance',
        ])->assertStatus(201);
    }

    public function test_rejects_duplicate_name_for_same_user(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'My Exercise']);
        Passport::actingAs($user);

        $this->postJson('/api/v1/exercises', [
            'name' => 'My Exercise',
            'type' => 'resistance',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validates_required_fields_for_store(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/v1/exercises', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_ignores_submitted_distance_unit(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/v1/exercises', [
            'name'            => 'Run',
            'type'            => 'distance',
            'type_attributes' => ['distance_unit' => 'miles', 'tracks_elevation' => true],
        ])->assertStatus(201)
            ->assertJsonPath('data.type_attributes.tracks_elevation', true)
            ->assertJsonMissingPath('data.type_attributes.distance_unit');
    }

    public function test_rejects_inaccessible_equipment_type(): void
    {
        $otherUser      = User::factory()->create();
        $otherEquipment = EquipmentType::create([
            'name'      => 'Other Gear',
            'user_id'   => $otherUser->id,
            'is_system' => false,
        ]);

        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/v1/exercises', [
            'name'              => 'Bad Exercise',
            'type'              => 'resistance',
            'equipment_type_id' => $otherEquipment->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('equipment_type_id');
    }

    // Update

    public function test_updates_own_exercise(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        Passport::actingAs($user);

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_updates_type_specific_attributes(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance([
            'bodyweight_base' => false,
        ])->create(['user_id' => $user->id, 'name' => 'Pull-up']);
        Passport::actingAs($user);

        $this->putJson("/api/v1/exercises/{$exercise->id}", [
            'name'            => 'Pull-up',
            'type_attributes' => ['bodyweight_base' => true, 'allows_added_weight' => true],
        ])->assertOk()
            ->assertJsonPath('data.type_attributes.bodyweight_base', true)
            ->assertJsonPath('data.type_attributes.allows_added_weight', true);
    }

    public function test_cannot_update_system_exercise(): void
    {
        $exercise = Exercise::factory()->resistance()->create(['name' => 'System Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'Renamed'])
            ->assertStatus(403);
    }

    public function test_cannot_update_other_users_exercise(): void
    {
        $owner    = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id, 'name' => 'Their Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'Stolen'])
            ->assertStatus(403);
    }

    public function test_update_returns_truthful_usage_counts(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'In Use Exercise']);
        Passport::actingAs($user);

        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        WorkoutEntry::create([
            'workout_id'  => $workout->id,
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $response = $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'In Use Exercise']);

        $response->assertOk()
            ->assertJsonPath('data.usage_count', 1)
            ->assertJsonPath('data.has_logged_data', true);
    }

    /** @param array<string, mixed> $typeAttributes */
    #[DataProvider('exerciseTypeProvider')]
    public function test_create_then_update_round_trip_per_type(string $type, array $typeAttributes): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $created = $this->postJson('/api/v1/exercises', [
            'name'            => 'Round Trip',
            'type'            => $type,
            'type_attributes' => $typeAttributes,
        ])->assertStatus(201);

        $response = $this->putJson("/api/v1/exercises/{$created->json('data.id')}", [
            'name'            => 'Round Trip',
            'type_attributes' => $typeAttributes,
        ])->assertOk();

        foreach ($typeAttributes as $key => $value) {
            $response->assertJsonPath("data.type_attributes.{$key}", $value);
        }
    }

    public function test_lazy_loading_prevention_is_enabled_outside_production(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    // Destroy

    public function test_deletes_own_exercise(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'To Delete']);
        Passport::actingAs($user);

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
        $this->assertDatabaseMissing('exercise_resistance', ['exercise_id' => $exercise->id]);
    }

    public function test_cannot_delete_system_exercise(): void
    {
        $exercise = Exercise::factory()->resistance()->create(['name' => 'System Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    public function test_cannot_delete_other_users_exercise(): void
    {
        $owner    = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $owner->id, 'name' => 'Their Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    public function test_returns_409_when_deleting_exercise_in_use_by_workout(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'In Use']);
        Passport::actingAs($user);

        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
    }

    public function test_entry_only_usage_agrees_across_index_show_and_destroy(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id, 'name' => 'Entry Only']);
        Passport::actingAs($user);

        $workout = Workout::factory()->create(['user_id' => $user->id]);
        WorkoutEntry::create([
            'workout_id'  => $workout->id,
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $row = collect($this->getJson('/api/v1/exercises')->assertOk()->json('data'))
            ->firstWhere('id', $exercise->id);
        $this->assertSame(0, $row['usage_count']);
        $this->assertTrue($row['has_logged_data']);

        $this->getJson("/api/v1/exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('data.usage_count', 0)
            ->assertJsonPath('data.has_logged_data', true);

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(409);
        $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
    }

    public function test_usage_annotations_are_scoped_to_the_requesting_user(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => null]);

        $workout = Workout::factory()->create(['user_id' => $other->id]);
        $workout->exercises()->attach($exercise->id, ['exercise_order' => 0]);
        WorkoutEntry::create([
            'workout_id'  => $workout->id,
            'exercise_id' => $exercise->id,
            'set_order'   => 0,
        ]);

        $template = WorkoutTemplate::factory()->create(['user_id' => $other->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($owner);

        $row = collect($this->getJson('/api/v1/exercises')->assertOk()->json('data'))
            ->firstWhere('id', $exercise->id);

        $this->assertSame(0, $row['usage_count']);
        $this->assertFalse($row['has_logged_data']);

        $this->getJson("/api/v1/exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('data.usage_count', 0)
            ->assertJsonPath('data.has_logged_data', false);
    }

    public function test_cannot_delete_exercise_referenced_by_template(): void
    {
        $user     = User::factory()->create();
        $exercise = Exercise::factory()->resistance()->create(['user_id' => $user->id]);
        $template = WorkoutTemplate::factory()->create(['user_id' => $user->id]);
        $template->exercises()->attach($exercise->id, ['exercise_order' => 0]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(409);
    }

    // Auth

    public function test_unauthenticated_cannot_access_exercises(): void
    {
        $this->getJson('/api/v1/exercises')->assertStatus(401);
        $this->postJson('/api/v1/exercises', ['name' => 'Nope'])->assertStatus(401);
    }
}
