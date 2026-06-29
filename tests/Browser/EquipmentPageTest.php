<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders and tests equipment page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);

        // Desktop
        $browser->resize(1920, 1080)
            ->visit('/equipment')
            ->waitFor('@equipment-page')
            ->assertVisible('@equipment-page')
            ->assertVisible('@equipment-list')
            ->assertVisible('@create-equipment-btn')
            ->assertSeeIn('@equipment-list', 'System')
            ->screenshot('equipment-desktop');

        // Tablet
        $browser->resize(768, 1024)
            ->visit('/equipment')
            ->waitFor('@equipment-page')
            ->assertVisible('@equipment-page')
            ->assertVisible('@equipment-list')
            ->assertVisible('@create-equipment-btn')
            ->screenshot('equipment-tablet');

        // Phone
        $browser->resize(375, 812)
            ->visit('/equipment')
            ->waitFor('@equipment-page')
            ->assertVisible('@equipment-page')
            ->assertVisible('@equipment-list')
            ->assertVisible('@create-equipment-btn')
            ->screenshot('equipment-phone');
    });
});
