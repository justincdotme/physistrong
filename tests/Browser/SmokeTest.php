<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the login page', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/login')
            ->waitForText('Welcome back')
            ->assertSee('Log In')
            ->screenshot('smoke-login');
    });
});

it('renders the app shell after login', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->assertVisible('@app-shell')
            ->screenshot('smoke-shell');
    });
});
