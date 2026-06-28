<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EquipmentType;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    private function createExercise(
        ?User $user,
        string $type = 'resistance',
        array $overrides = [],
        array $typeAttributes = [],
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
        };

        $childRelation = match ($type) {
            'resistance' => 'resistance',
            'timed_hold' => 'timedHold',
            'distance' => 'distance',
            'interval' => 'interval',
        };

        $exercise->$childRelation()->create(array_merge($defaults, $typeAttributes));

        return $exercise;
    }

    // -- Index --

    public function test_lists_system_and_own_exercises(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->createExercise(null, 'resistance', ['name' => 'System Bench Press']);
        $this->createExercise($user, 'resistance', ['name' => 'My Custom Exercise']);
        $this->createExercise($other, 'resistance', ['name' => 'Other User Exercise']);

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
        $this->createExercise(null, 'resistance', ['name' => 'Bench Press']);
        $this->createExercise(null, 'distance', ['name' => 'Treadmill Run']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises?type=resistance');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Bench Press'));
        $this->assertFalse($names->contains('Treadmill Run'));
    }

    public function test_filters_exercises_by_equipment_type(): void
    {
        $barbell = EquipmentType::create(['name' => 'barbell', 'user_id' => null, 'is_system' => true]);
        $dumbbell = EquipmentType::create(['name' => 'dumbbell', 'user_id' => null, 'is_system' => true]);

        $user = User::factory()->create();
        $this->createExercise(null, 'resistance', ['name' => 'Barbell Curl', 'equipment_type_id' => $barbell->id]);
        $this->createExercise(null, 'resistance', ['name' => 'Dumbbell Curl', 'equipment_type_id' => $dumbbell->id]);

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
        $this->createExercise(null, 'resistance', ['name' => 'Bench Press']);
        $this->createExercise(null, 'resistance', ['name' => 'Deadlift']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/exercises?search=bench');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Bench Press'));
        $this->assertFalse($names->contains('Deadlift'));
    }

    // -- Show --

    public function test_shows_exercise_with_type_attributes(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise(null, 'resistance', ['name' => 'Pull-up'], [
            'bodyweight_base' => true,
            'allows_added_weight' => true,
            'bilateral' => true,
        ]);

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
        $owner = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance', ['name' => 'Secret Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    // -- Store --

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
                ['distance_unit' => 'miles', 'tracks_elevation' => true],
            ],
            'interval' => [
                'interval',
                ['default_work_seconds' => 30, 'default_rest_seconds' => 15, 'default_rounds' => 8],
            ],
        ];
    }

    #[DataProvider('exerciseTypeProvider')]
    public function test_creates_exercise_for_each_cti_type(string $type, array $typeAttributes): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/exercises', [
            'name' => "Test {$type} exercise",
            'type' => $type,
            'type_attributes' => $typeAttributes,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', $type)
            ->assertJsonPath('data.name', "Test {$type} exercise")
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('exercises', [
            'name' => "Test {$type} exercise",
            'type' => $type,
            'user_id' => $user->id,
        ]);

        $exerciseId = $response->json('data.id');
        $childTable = match ($type) {
            'resistance' => 'exercise_resistance',
            'timed_hold' => 'exercise_timed_hold',
            'distance' => 'exercise_distance',
            'interval' => 'exercise_interval',
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
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/exercises', [
            'name' => 'Barbell Squat',
            'type' => 'resistance',
            'equipment_type_id' => $barbell->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.equipment_type_id', $barbell->id)
            ->assertJsonPath('data.equipment_type.name', 'barbell');
    }

    public function test_allows_same_name_as_system_exercise(): void
    {
        $this->createExercise(null, 'resistance', ['name' => 'Bench Press']);
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
        $this->createExercise($user, 'resistance', ['name' => 'My Exercise']);
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

    public function test_validates_distance_unit_required_for_distance_type(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/v1/exercises', [
            'name' => 'Run',
            'type' => 'distance',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('type_attributes.distance_unit');
    }

    public function test_rejects_inaccessible_equipment_type(): void
    {
        $otherUser = User::factory()->create();
        $otherEquipment = EquipmentType::create([
            'name' => 'Other Gear',
            'user_id' => $otherUser->id,
            'is_system' => false,
        ]);

        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/v1/exercises', [
            'name' => 'Bad Exercise',
            'type' => 'resistance',
            'equipment_type_id' => $otherEquipment->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('equipment_type_id');
    }

    // -- Update --

    public function test_updates_own_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Old Name']);
        Passport::actingAs($user);

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_updates_type_specific_attributes(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'Pull-up'], [
            'bodyweight_base' => false,
        ]);
        Passport::actingAs($user);

        $this->putJson("/api/v1/exercises/{$exercise->id}", [
            'name' => 'Pull-up',
            'type_attributes' => ['bodyweight_base' => true, 'allows_added_weight' => true],
        ])->assertOk()
            ->assertJsonPath('data.type_attributes.bodyweight_base', true)
            ->assertJsonPath('data.type_attributes.allows_added_weight', true);
    }

    public function test_cannot_update_system_exercise(): void
    {
        $exercise = $this->createExercise(null, 'resistance', ['name' => 'System Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'Renamed'])
            ->assertStatus(403);
    }

    public function test_cannot_update_other_users_exercise(): void
    {
        $owner = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance', ['name' => 'Their Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->putJson("/api/v1/exercises/{$exercise->id}", ['name' => 'Stolen'])
            ->assertStatus(403);
    }

    // -- Destroy --

    public function test_deletes_own_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = $this->createExercise($user, 'resistance', ['name' => 'To Delete']);
        Passport::actingAs($user);

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
        $this->assertDatabaseMissing('exercise_resistance', ['exercise_id' => $exercise->id]);
    }

    public function test_cannot_delete_system_exercise(): void
    {
        $exercise = $this->createExercise(null, 'resistance', ['name' => 'System Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    public function test_cannot_delete_other_users_exercise(): void
    {
        $owner = User::factory()->create();
        $exercise = $this->createExercise($owner, 'resistance', ['name' => 'Their Exercise']);

        Passport::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(403);
    }

    // -- Auth --

    public function test_unauthenticated_cannot_access_exercises(): void
    {
        $this->getJson('/api/v1/exercises')->assertStatus(401);
        $this->postJson('/api/v1/exercises', ['name' => 'Nope'])->assertStatus(401);
    }
}
