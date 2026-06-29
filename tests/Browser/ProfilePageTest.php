<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders and tests profile page', function () {
    $user = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);

        // Desktop
        $browser->resize(1920, 1080)
            ->visit('/profile')
            ->waitFor('@profile-page')
            ->assertVisible('@profile-page')
            ->assertSee('Test User')
            ->assertSee('test@example.com')
            ->assertSee('Measurement system')
            ->assertSee('Imperial (lb, mi)')
            ->assertSee('Metric (kg, km)')
            ->assertSee('Theme')
            ->assertVisible('@logout-btn')
            ->assertSee('Log Out')
            ->screenshot('profile-desktop');

        // Tablet
        $browser->resize(768, 1024)
            ->visit('/profile')
            ->waitFor('@profile-page')
            ->assertVisible('@profile-page')
            ->screenshot('profile-tablet');

        // Phone
        $browser->resize(375, 812)
            ->visit('/profile')
            ->waitFor('@profile-page')
            ->assertVisible('@profile-page')
            ->screenshot('profile-phone');
    });
});

it('logout button redirects to login page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/profile')
            ->waitFor('@logout-btn')
            ->click('@logout-btn')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});
