<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TokenBlacklist;
use Illuminate\Console\Command;
use Laravel\Passport\Token;

class RebuildTokenBlacklist extends Command
{
    protected $signature = 'auth:rebuild-token-blacklist';

    protected $description = 'Restore revoked-token blacklist entries from the database';

    public function handle(TokenBlacklist $blacklist): int
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
