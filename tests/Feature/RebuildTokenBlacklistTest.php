<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TokenBlacklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Token;
use Tests\TestCase;

class RebuildTokenBlacklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_repopulates_blacklist_for_revoked_unexpired_tokens(): void
    {
        $this->freezeTime();

        $token = $this->createToken([
            'revoked' => true,
            'expires_at' => now()->addDays(15),
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $this->assertTrue(app(TokenBlacklistService::class)->has($token->getKey()));
    }

    public function test_skips_expired_revoked_tokens(): void
    {
        $this->freezeTime();

        $token = $this->createToken([
            'revoked' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $this->assertFalse(app(TokenBlacklistService::class)->has($token->getKey()));
    }

    public function test_skips_unrevoked_tokens(): void
    {
        $this->freezeTime();

        $token = $this->createToken([
            'revoked' => false,
            'expires_at' => now()->addDays(15),
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $this->assertFalse(app(TokenBlacklistService::class)->has($token->getKey()));
    }

    public function test_sets_ttl_to_remaining_token_lifetime(): void
    {
        $this->freezeTime();

        $expiresAt = now()->addDays(10);

        $token = $this->createToken([
            'revoked' => true,
            'expires_at' => $expiresAt,
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $key = 'auth:revoked-jti:'.$token->getKey();

        $this->travelTo($expiresAt->copy()->subMinute());
        $this->assertTrue(Cache::has($key));

        $this->travelTo($expiresAt->copy()->addMinute());
        $this->assertFalse(Cache::has($key));
    }

    public function test_is_idempotent(): void
    {
        $this->freezeTime();

        $token = $this->createToken([
            'revoked' => true,
            'expires_at' => now()->addDays(15),
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();
        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $this->assertTrue(app(TokenBlacklistService::class)->has($token->getKey()));
    }

    public function test_handles_mix_of_token_states(): void
    {
        $this->freezeTime();

        $revokedUnexpired = $this->createToken([
            'revoked' => true,
            'expires_at' => now()->addDays(15),
        ]);

        $revokedExpired = $this->createToken([
            'revoked' => true,
            'expires_at' => now()->subDay(),
        ]);

        $activeUnexpired = $this->createToken([
            'revoked' => false,
            'expires_at' => now()->addDays(15),
        ]);

        $this->artisan('auth:rebuild-token-blacklist')->assertSuccessful();

        $blacklist = app(TokenBlacklistService::class);
        $this->assertTrue($blacklist->has($revokedUnexpired->getKey()));
        $this->assertFalse($blacklist->has($revokedExpired->getKey()));
        $this->assertFalse($blacklist->has($activeUnexpired->getKey()));
    }

    private function createToken(array $attributes): Token
    {
        return Token::forceCreate([
            'id' => fake()->uuid(),
            'user_id' => 1,
            'client_id' => fake()->uuid(),
            'revoked' => $attributes['revoked'],
            'expires_at' => $attributes['expires_at'],
        ]);
    }
}
