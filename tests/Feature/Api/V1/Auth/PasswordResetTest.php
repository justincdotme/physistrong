<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/password/forgot', ['email' => 'user@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_returns_ok_for_unknown_email(): void
    {
        $this->postJson('/api/v1/password/forgot', ['email' => 'nobody@example.com'])
            ->assertOk();
    }

    public function test_forgot_validates_email(): void
    {
        $this->postJson('/api/v1/password/forgot', ['email' => 'not-an-email'])
            ->assertStatus(422);
    }

    public function test_resets_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/password/reset', [
            'email' => 'user@example.com',
            'token' => $token,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertOk();
    }

    public function test_rejects_invalid_reset_token(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/password/reset', [
            'email' => 'user@example.com',
            'token' => 'bad-token',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(422);
    }

    public function test_reset_validates_password_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/password/reset', [
            'email' => 'user@example.com',
            'token' => $token,
            'password' => 'newpassword',
            'password_confirmation' => 'mismatch',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_forgot_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'user@example.com']);
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/password/forgot', ['email' => 'user@example.com']);
        }

        $this->postJson('/api/v1/password/forgot', ['email' => 'user@example.com'])
            ->assertStatus(429);
    }

    public function test_reset_link_contains_email_and_spa_path(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/password/forgot', ['email' => 'user@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/password/reset/')
                && str_contains($url, 'email=user%40example.com');
        });
    }
}
