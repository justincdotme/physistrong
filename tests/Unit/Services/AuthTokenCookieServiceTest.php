<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AuthTokenCookieService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class AuthTokenCookieServiceTest extends TestCase
{
    public function test_issue_builds_a_hardened_api_scoped_cookie(): void
    {
        $expiresAt = Carbon::parse('2026-08-02 12:00:00', 'UTC');

        $cookie = AuthTokenCookieService::issue('jwt-value', $expiresAt);

        $this->assertSame('ps_token', $cookie->getName());
        $this->assertSame('jwt-value', $cookie->getValue());
        $this->assertSame($expiresAt->getTimestamp(), $cookie->getExpiresTime());
        $this->assertSame('/api/v1', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function test_expire_builds_an_expired_cookie_on_the_same_name_and_path(): void
    {
        $cookie = AuthTokenCookieService::expire();

        $this->assertSame('ps_token', $cookie->getName());
        $this->assertSame('/api/v1', $cookie->getPath());
        $this->assertLessThan(time(), $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function test_secure_flag_follows_session_config_for_http_deploys(): void
    {
        config(['session.secure' => false]);

        $cookie = AuthTokenCookieService::issue('jwt-value', Carbon::parse('2026-08-02 12:00:00', 'UTC'));

        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }
}
