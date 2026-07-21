<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Services\AuthTokenCookieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesPassportToken;
use Tests\TestCase;

class CookieAuthenticationTest extends TestCase
{
    use CreatesPassportToken;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    public function test_authenticates_with_only_the_token_cookie(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth')->accessToken;

        $this->withCredentials()
            ->withUnencryptedCookie(AuthTokenCookieService::NAME, $token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_authorization_header_wins_over_the_cookie(): void
    {
        $headerUser = User::factory()->create(['email' => 'header@example.com']);
        $cookieUser = User::factory()->create(['email' => 'cookie@example.com']);

        $headerToken = $headerUser->createToken('auth')->accessToken;
        $cookieToken = $cookieUser->createToken('auth')->accessToken;

        $this->withCredentials()
            ->withToken($headerToken)
            ->withUnencryptedCookie(AuthTokenCookieService::NAME, $cookieToken)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'header@example.com');
    }

    public function test_rejects_a_garbage_cookie_value(): void
    {
        User::factory()->create();

        $this->withCredentials()
            ->withUnencryptedCookie(AuthTokenCookieService::NAME, 'not-a-jwt')
            ->getJson('/api/v1/user')
            ->assertStatus(401);
    }

    public function test_logout_expires_the_cookie_and_rejects_the_replayed_token(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'secret123',
        ]);

        $token = $this->loginAndReadTokenCookie('user@example.com', 'secret123');

        $this->withCredentials()
            ->withUnencryptedCookie(AuthTokenCookieService::NAME, $token)
            ->postJson('/api/v1/logout')
            ->assertNoContent()
            ->assertCookieExpired(AuthTokenCookieService::NAME);

        // In-process test requests share one guard instance; drop it when
        // re-presenting the same token the way a real per-request process would.
        $this->app['auth']->forgetGuards();

        $this->withCredentials()
            ->withUnencryptedCookie(AuthTokenCookieService::NAME, $token)
            ->getJson('/api/v1/user')
            ->assertStatus(401);
    }
}
