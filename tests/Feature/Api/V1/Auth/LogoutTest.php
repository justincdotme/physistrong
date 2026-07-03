<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Services\AuthTokenCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_revokes_current_token_and_expires_the_cookie(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/v1/logout')
            ->assertNoContent()
            ->assertCookieExpired(AuthTokenCookie::NAME);
    }

    public function test_rejects_unauthenticated_request(): void
    {
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }
}
