<?php

declare(strict_types=1);

namespace Tests;

use App\Services\AuthTokenCookieService;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Symfony\Component\HttpFoundation\Cookie;

trait CreatesPassportToken
{
    protected function setUpPassport(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }

    protected function loginAndReadTokenCookie(string $email, string $password): string
    {
        $cookie = $this->postJson('/api/v1/login', [
            'email'    => $email,
            'password' => $password,
        ])->getCookie(AuthTokenCookieService::NAME, decrypt: false);

        $this->assertNotNull($cookie, 'Login response did not set the auth token cookie.');

        return (string) $cookie->getValue();
    }

    protected function assertIssuesAuthTokenCookie(TestResponse $response): void
    {
        $cookie = $response->getCookie(AuthTokenCookieService::NAME, decrypt: false);

        $this->assertNotNull($cookie, 'Response did not set the auth token cookie.');
        $this->assertNotSame('', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertSame('/api/v1', $cookie->getPath());
        // The cookie must live as long as the 30-day token, not default to session scope.
        $this->assertGreaterThan(now()->addDays(29)->getTimestamp(), $cookie->getExpiresTime());
    }
}
