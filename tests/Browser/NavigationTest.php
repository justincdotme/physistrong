<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('shows desktop sidebar nav at desktop width', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@app-shell')
            ->assertVisible('@desktop-nav')
            ->assertMissing('@mobile-bottom-nav')
            ->assertMissing('@mobile-top-bar')
            ->screenshot('nav-desktop-sidebar');
    });
});

it('displays all four nav items in desktop sidebar', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->assertSee('Workouts')
            ->assertSee('Exercises')
            ->assertSee('Progress')
            ->assertSee('Equipment');
    });
});

it('shows profile avatar in desktop sidebar', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->assertVisible('@desktop-profile-link')
            ->assertSee('View profile')
            ->screenshot('nav-desktop-profile');
    });
});

it('shows mobile nav at tablet breakpoint', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/workouts')
            ->waitFor('@app-shell')
            ->assertVisible('@desktop-nav')
            ->assertMissing('@mobile-bottom-nav')
            ->screenshot('nav-tablet');
    });
});

it('shows mobile layout at phone width', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@app-shell')
            ->assertVisible('@mobile-top-bar')
            ->assertVisible('@mobile-bottom-nav')
            ->assertMissing('@desktop-nav')
            ->screenshot('nav-phone-layout');
    });
});

it('displays logo and profile in mobile top bar', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-top-bar')
            ->assertSee('Physistrong')
            ->assertVisible('@mobile-profile-link')
            ->assertSee('Jane')
            ->screenshot('nav-phone-top-bar');
    });
});

it('displays four nav items in mobile bottom tab bar', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->assertSee('Workouts')
            ->assertSee('Exercises')
            ->assertSee('Progress')
            ->assertSee('Equipment')
            ->screenshot('nav-phone-bottom-nav');
    });
});

it('navigates to exercises from desktop nav', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav button:contains("Exercises")')
            ->waitForLocation('/exercises')
            ->assertPathIs('/exercises');
    });
});

it('navigates to equipment from desktop nav', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav button:contains("Equipment")')
            ->waitForLocation('/equipment')
            ->assertPathIs('/equipment');
    });
});

it('navigates to progress from desktop nav', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav button:contains("Progress")')
            ->waitForLocation('/progress')
            ->assertPathIs('/progress');
    });
});

it('navigates to exercises from mobile bottom nav', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav button:contains("Exercises")')
            ->waitForLocation('/exercises')
            ->assertPathIs('/exercises');
    });
});

it('navigates to equipment from mobile bottom nav', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav button:contains("Equipment")')
            ->waitForLocation('/equipment')
            ->assertPathIs('/equipment');
    });
});

it('navigates to profile from desktop profile link', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-profile-link')
            ->click('@desktop-profile-link')
            ->waitForLocation('/profile')
            ->assertPathIs('/profile');
    });
});

it('navigates to profile from mobile profile link', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-profile-link')
            ->click('@mobile-profile-link')
            ->waitForLocation('/profile')
            ->assertPathIs('/profile');
    });
});

it('navigates to workouts from logo in desktop sidebar', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/exercises')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav button:contains("Physistrong")')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});

it('navigates to workouts from logo in mobile top bar', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('@mobile-top-bar')
            ->click('@mobile-top-bar button:contains("Physistrong")')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});

it('logs out from profile page and redirects to login', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/profile')
            ->waitFor('@logout-btn')
            ->click('@logout-btn')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

it('maintains nav state when navigating between sections at desktop', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->assertVisible('@desktop-nav')
            ->click('@desktop-nav button:contains("Exercises")')
            ->waitForLocation('/exercises')
            ->assertVisible('@desktop-nav')
            ->click('@desktop-nav button:contains("Progress")')
            ->waitForLocation('/progress')
            ->assertVisible('@desktop-nav');
    });
});

it('maintains nav state when navigating between sections on mobile', function () {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->assertVisible('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav button:contains("Exercises")')
            ->waitForLocation('/exercises')
            ->assertVisible('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav button:contains("Equipment")')
            ->waitForLocation('/equipment')
            ->assertVisible('@mobile-bottom-nav');
    });
});
