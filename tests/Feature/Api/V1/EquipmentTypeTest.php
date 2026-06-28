<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Database\Seeders\EquipmentTypeSeeder;

class EquipmentTypeTest extends TestCase
{
    use RefreshDatabase;

    private function seedSystemTypes(): void
    {
        $this->seed(EquipmentTypeSeeder::class);
    }

    public function test_lists_system_and_own_custom_types(): void
    {
        $this->seedSystemTypes();
        $user = User::factory()->create();
        EquipmentType::create(['name' => 'My Rack', 'user_id' => $user->id, 'is_system' => false]);

        $other = User::factory()->create();
        EquipmentType::create(['name' => 'Other Gear', 'user_id' => $other->id, 'is_system' => false]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/equipment-types');

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('barbell'));
        $this->assertTrue($names->contains('My Rack'));
        $this->assertFalse($names->contains('Other Gear'));
    }

    public function test_creates_custom_equipment_type(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/equipment-types', ['name' => 'Trap Bar'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Trap Bar')
            ->assertJsonPath('data.is_system', false);

        $this->assertDatabaseHas('equipment_types', [
            'name' => 'Trap Bar',
            'user_id' => $user->id,
            'is_system' => false,
        ]);
    }

    public function test_rejects_duplicate_name_for_same_user(): void
    {
        $user = User::factory()->create();
        EquipmentType::create(['name' => 'Trap Bar', 'user_id' => $user->id, 'is_system' => false]);
        Passport::actingAs($user);

        $this->postJson('/api/v1/equipment-types', ['name' => 'Trap Bar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_shows_single_equipment_type(): void
    {
        $this->seedSystemTypes();
        $user = User::factory()->create();
        Passport::actingAs($user);

        $type = EquipmentType::where('name', 'barbell')->first();

        $this->getJson("/api/v1/equipment-types/{$type->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'barbell');
    }

    public function test_updates_own_custom_type(): void
    {
        $user = User::factory()->create();
        $type = EquipmentType::create(['name' => 'Old Name', 'user_id' => $user->id, 'is_system' => false]);
        Passport::actingAs($user);

        $this->putJson("/api/v1/equipment-types/{$type->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_cannot_update_system_type(): void
    {
        $this->seedSystemTypes();
        $user = User::factory()->create();
        Passport::actingAs($user);

        $type = EquipmentType::where('name', 'barbell')->first();

        $this->putJson("/api/v1/equipment-types/{$type->id}", ['name' => 'Renamed'])
            ->assertStatus(403);
    }

    public function test_cannot_update_other_users_type(): void
    {
        $other = User::factory()->create();
        $type = EquipmentType::create(['name' => 'Their Gear', 'user_id' => $other->id, 'is_system' => false]);

        Passport::actingAs(User::factory()->create());

        $this->putJson("/api/v1/equipment-types/{$type->id}", ['name' => 'Stolen'])
            ->assertStatus(403);
    }

    public function test_deletes_unused_custom_type(): void
    {
        $user = User::factory()->create();
        $type = EquipmentType::create(['name' => 'To Delete', 'user_id' => $user->id, 'is_system' => false]);
        Passport::actingAs($user);

        $this->deleteJson("/api/v1/equipment-types/{$type->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('equipment_types', ['id' => $type->id]);
    }

    public function test_returns_409_when_deleting_referenced_type(): void
    {
        $user = User::factory()->create();
        $type = EquipmentType::create(['name' => 'In Use', 'user_id' => $user->id, 'is_system' => false]);

        DB::table('exercises')->insert([
            'name' => 'Test Exercise',
            'type' => 'resistance',
            'user_id' => $user->id,
            'equipment_type_id' => $type->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Passport::actingAs($user);

        $this->deleteJson("/api/v1/equipment-types/{$type->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('equipment_types', ['id' => $type->id]);
    }

    public function test_cannot_delete_system_type(): void
    {
        $this->seedSystemTypes();
        $user = User::factory()->create();
        Passport::actingAs($user);

        $type = EquipmentType::where('name', 'barbell')->first();

        $this->deleteJson("/api/v1/equipment-types/{$type->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_equipment(): void
    {
        $this->getJson('/api/v1/equipment-types')->assertStatus(401);
        $this->postJson('/api/v1/equipment-types', ['name' => 'Nope'])->assertStatus(401);
    }
}
