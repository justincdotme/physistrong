<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the exercises page and displays system exercises', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/exercises')
            ->waitFor('@exercises-page')
            ->assertVisible('@exercise-list')
            ->assertVisible('@exercise-search')
            ->assertVisible('@type-filter')
            ->assertVisible('@equipment-filter')
            ->assertVisible('@create-exercise-btn')
            ->assertSee('Bench Press')
            ->screenshot('exercises-desktop');
    });
});

it('exercises page renders at tablet width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/exercises')
            ->waitFor('@exercises-page')
            ->assertVisible('@exercise-list')
            ->assertVisible('@exercise-search')
            ->assertVisible('@create-exercise-btn')
            ->screenshot('exercises-tablet');
    });
});

it('exercises page renders at phone width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('@exercises-page')
            ->assertVisible('@create-exercise-btn')
            ->assertVisible('@exercise-list')
            ->screenshot('exercises-phone');
    });
});

it('search filters exercises by name', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercise-list')
            ->type('@exercise-search', 'Bench')
            ->pause(500)
            ->assertSee('Bench Press')
            ->screenshot('exercises-searched');
    });
});

it('navigates to exercise detail page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link');
    });
});

it('exercise detail displays at desktop width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link')
            ->screenshot('exercise-detail-desktop');
    });
});

it('exercise detail displays at tablet width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->screenshot('exercise-detail-tablet');
    });
});

it('exercise detail displays at phone width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link')
            ->screenshot('exercise-detail-phone');
    });
});

it('shows progress link on exercise detail', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@progress-link')
            ->assertSee('View Progress');
    });
});

it('hides delete button on system exercise', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercise-list')
            ->click('@exercise-list')
            ->waitFor('@exercise-detail-page')
            ->assertMissing('@delete-exercise-btn');
    });
});
