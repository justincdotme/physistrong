<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders and tests equipment page', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);

        // Desktop
        $browser->resize(1920, 1080)
            ->visit('/equipment')
            ->waitFor('@equipment-page')
            ->assertVisible('@equipment-page')
            ->assertVisible('@equipment-list')
            ->assertVisible('@create-equipment-btn')
            ->assertSeeIn('@equipment-list', 'SYSTEM')
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

it('disables delete button for system equipment types', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/equipment')
            ->waitFor('@equipment-page')
            ->waitFor('@equipment-list')
            ->screenshot('equipment-delete-guards');

        // System equipment types should have disabled delete buttons
        // The seeded equipment includes system types with exercises using them
        $disabledButtons = $browser->elements('button[disabled]');
        $this->assertNotEmpty($disabledButtons, 'System equipment should have at least one disabled delete button');
    });
});
