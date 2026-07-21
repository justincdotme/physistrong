<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TokenBlacklistService;
use Illuminate\Console\Command;
use Laravel\Passport\Token;

class RebuildTokenBlacklist extends Command
{
    /** @var string */
    protected $signature = 'auth:rebuild-token-blacklist';

    /** @var string */
    protected $description = 'Restore revoked-token blacklist entries from the database';

    /**
     * @param TokenBlacklistService $blacklist
     *
     * @return integer
     */
    public function handle(TokenBlacklistService $blacklist): int
    {
        $count = 0;

        Token::query()
            ->where('revoked', true)
            ->where('expires_at', '>', now())
            ->each(function (Token $token) use ($blacklist, &$count): void {
                $blacklist->add($token->getKey(), $token->expires_at);
                $count++;
            });

        $this->info("Restored {$count} blacklist entries.");

        return self::SUCCESS;
    }
}
