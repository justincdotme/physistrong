<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the progress page at desktop width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-desktop');
    });
});

it('renders the progress page at tablet width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-tablet');
    });
});

it('renders the progress page at phone width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-phone');
    });
});
