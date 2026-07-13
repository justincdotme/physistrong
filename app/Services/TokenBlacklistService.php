<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

class TokenBlacklistService
{
    private const KEY_PREFIX = 'auth:revoked-jti:';

    public function __construct(private Cache $cache) {}

    public function add(string $jti, DateTimeInterface $expiresAt): void
    {
        // put() with a past expiry stores nothing, so expired tokens never create entries
        $this->cache->put(self::KEY_PREFIX.$jti, true, $expiresAt);
    }

    public function has(string $jti): bool
    {
        return $this->cache->has(self::KEY_PREFIX.$jti);
    }
}
