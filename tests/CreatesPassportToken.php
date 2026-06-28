<?php

declare(strict_types=1);

namespace Tests;

use Laravel\Passport\ClientRepository;

trait CreatesPassportToken
{
    protected function setUpPassport(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }
}
