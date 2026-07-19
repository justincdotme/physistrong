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

    /**
     * @param string            $token
     * @param DateTimeInterface $expiresAt
     *
     * @return Cookie
     */
    public static function issue(string $token, DateTimeInterface $expiresAt): Cookie
    {
        return self::make($token, $expiresAt);
    }

    /**
     * @return Cookie
     */
    public static function expire(): Cookie
    {
        return self::make('', 1);
    }

    /**
     * @param string                    $value
     * @param DateTimeInterface|integer $expire
     *
     * @return Cookie
     */
    private static function make(string $value, DateTimeInterface|int $expire): Cookie
    {
        // Secure by default; only an explicit SESSION_SECURE_COOKIE=false (the
        // no-TLS install path) may relax it, or HTTP deploys cannot log in.
        // Normalized because env plumbing (phpunit <env>, exotic .env values)
        // can surface the flag as a string, and the Cookie arg is ?bool.
        $secure = config('session.secure');

        return new Cookie(
            name: self::NAME,
            value: $value,
            expire: $expire,
            path: self::PATH,
            secure: $secure === null ? true : filter_var($secure, FILTER_VALIDATE_BOOL),
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }
}
