<?php

declare(strict_types=1);

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class HomePage extends Page
{
    public function url(): string
    {
        return '/';
    }

    /**
     * @param Browser $browser
     */
    public function assert(Browser $browser): void
    {
        //
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@element' => '#selector',
        ];
    }
}
