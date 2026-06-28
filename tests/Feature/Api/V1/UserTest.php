<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\CreatesPassportToken;
use Tests\TestCase;

class UserTest extends TestCase
{
    use CreatesPassportToken;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    public function test_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure([
                'data' => ['id', 'email', 'first_name', 'last_name', 'measurement_system', 'theme'],
            ]);
    }

    public function test_rejects_unauthenticated(): void
    {
        $this->getJson('/api/v1/user')->assertStatus(401);
    }

    public function test_updates_profile_fields(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'measurement_system' => 'metric',
            'theme' => 'dark',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.measurement_system', 'metric')
            ->assertJsonPath('data.theme', 'dark');
    }

    public function test_updates_email_with_uniqueness(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_allows_keeping_own_email(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', [
            'email' => 'mine@example.com',
            'first_name' => 'Updated',
        ])->assertOk();
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_change_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertOk();

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Passport::actingAs($user);

        $this->putJson('/api/v1/user', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }
}
