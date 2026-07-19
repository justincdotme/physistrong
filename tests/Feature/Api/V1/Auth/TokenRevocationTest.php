<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Token;
use Tests\CreatesPassportToken;
use Tests\TestCase;

class TokenRevocationTest extends TestCase
{
    use CreatesPassportToken;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPassport();
    }

    public function test_logout_blacklists_the_token_for_its_remaining_lifetime(): void
    {
        $this->freezeTime();

        $token = $this->login($this->createUser());

        $dbToken   = Token::query()->sole();
        $expiresAt = $dbToken->expires_at;

        $this->travelTo(now()->addDays(10));

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        $key = 'auth:revoked-jti:' . $dbToken->getKey();

        $this->assertTrue(Cache::has($key));
        $this->assertTrue($dbToken->fresh()->revoked);

        $this->travelTo($expiresAt->copy()->subMinute());
        $this->assertTrue(Cache::has($key));

        $this->travelTo($expiresAt->copy()->addMinute());
        $this->assertFalse(Cache::has($key));
    }

    public function test_rejects_a_blacklisted_token_even_when_its_database_row_is_not_revoked(): void
    {
        $token = $this->login($this->createUser());

        $this->withToken($token)->getJson('/api/v1/user')->assertOk();

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        Token::query()->update(['revoked' => false]);

        $this->withToken($token)->getJson('/api/v1/user')->assertStatus(401);
    }

    public function test_logout_leaves_other_tokens_for_the_same_user_valid(): void
    {
        $user = $this->createUser();

        $firstToken  = $this->login($user);
        $secondToken = $this->login($user);

        $this->withToken($firstToken)->postJson('/api/v1/logout')->assertNoContent();

        // In-process test requests share one guard instance, which caches the resolved
        // user and its access token; drop it when switching tokens the way a real
        // per-request process would.
        $this->app['auth']->forgetGuards();

        $this->withToken($secondToken)->getJson('/api/v1/user')->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($firstToken)->getJson('/api/v1/user')->assertStatus(401);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'secret123',
        ]);
    }

    private function login(User $user): string
    {
        return $this->loginAndReadTokenCookie($user->email, 'secret123');
    }
}
