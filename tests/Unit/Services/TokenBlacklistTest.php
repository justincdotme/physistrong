<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TokenBlacklist;
use Tests\TestCase;

class TokenBlacklistTest extends TestCase
{
    public function test_expired_tokens_write_no_blacklist_entry(): void
    {
        $blacklist = app(TokenBlacklist::class);

        $blacklist->add('live-token-jti', now()->addDay());
        $blacklist->add('expired-token-jti', now()->subSecond());

        $this->assertTrue($blacklist->has('live-token-jti'));
        $this->assertFalse($blacklist->has('expired-token-jti'));
    }
}
