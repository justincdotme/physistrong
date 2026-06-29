<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the login page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitForText('Welcome back')
            ->assertSee('Log In')
            ->screenshot('smoke-login');
    });
});

it('renders the app shell after login', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->assertVisible('@app-shell')
            ->screenshot('smoke-shell');
    });
});
