<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Cookie;

class AuthTokenCookieService
{
    public const NAME = 'ps_token';

    // Path scoping keeps the cookie off web routes entirely, so only API
    // requests ever carry the token.
    private const PATH = '/api/v1';

    public static function issue(string $token, DateTimeInterface $expiresAt): Cookie
    {
        return self::make($token, $expiresAt);
    }

    public static function expire(): Cookie
    {
        return self::make('', 1);
    }

    private static function make(string $value, DateTimeInterface|int $expire): Cookie
    {
        return new Cookie(
            name: self::NAME,
            value: $value,
            expire: $expire,
            path: self::PATH,
            secure: true,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }
}
