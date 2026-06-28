<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\MeasurementSystem;
use App\Enums\ThemePreference;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\CreatesPassportToken;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use CreatesPassportToken;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'measurement_system' => 'imperial',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ], $overrides);
    }

    public function test_registers_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'first_name', 'last_name', 'measurement_system', 'theme'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $user = User::where('email', 'new@example.com')->first();
        $this->assertSame(MeasurementSystem::Imperial, $user->measurement_system);
        $this->assertSame(ThemePreference::System, $user->theme);
    }

    public function test_fires_registered_event(): void
    {
        Event::fake([Registered::class]);

        $this->postJson('/api/v1/register', $this->validPayload());

        Event::assertDispatched(Registered::class);
    }

    public function test_measurement_system_is_required(): void
    {
        $this->postJson('/api/v1/register', $this->validPayload([
            'measurement_system' => null,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('measurement_system');
    }

    public function test_rejects_invalid_measurement_system(): void
    {
        $this->postJson('/api/v1/register', $this->validPayload([
            'measurement_system' => 'cubits',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('measurement_system');
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson('/api/v1/register', $this->validPayload([
            'password_confirmation' => 'wrong',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_minimum_length(): void
    {
        $this->postJson('/api/v1/register', $this->validPayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', $this->validPayload([
            'email' => 'taken@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_first_and_last_name_are_optional(): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'first_name' => null,
            'last_name' => null,
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'first_name' => null]);
    }

    public function test_sends_welcome_notification(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/register', $this->validPayload());

        $user = User::where('email', 'new@example.com')->first();
        Notification::assertSentTo($user, WelcomeNotification::class);
    }
}
