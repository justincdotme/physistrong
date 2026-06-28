<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }

    public function test_logs_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_rejects_nonexistent_email(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ])->assertStatus(401);
    }

    public function test_validates_required_fields(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'user@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }
}
