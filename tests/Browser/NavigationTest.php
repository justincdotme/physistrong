<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('shows desktop sidebar nav at desktop width', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
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

it('displays all four nav items in desktop sidebar', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
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

it('shows profile avatar in desktop sidebar', function (): void {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ]);
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->assertVisible('@desktop-profile-link')
            ->assertSee('View profile')
            ->screenshot('nav-desktop-profile');
    });
});

it('shows mobile nav at tablet breakpoint', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/workouts')
            ->waitFor('@app-shell')
            ->assertVisible('@desktop-nav')
            ->assertMissing('@mobile-bottom-nav')
            ->screenshot('nav-tablet');
    });
});

it('shows mobile layout at phone width', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
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

it('displays logo and profile in mobile top bar', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name'  => 'Smith',
    ]);
    $this->browse(function (Browser $browser) use ($user): void {
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

it('displays four nav items in mobile bottom tab bar', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
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

it('navigates to exercises from desktop nav', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav-exercises')
            ->waitForLocation('/exercises')
            ->assertPathIs('/exercises');
    });
});

it('navigates to equipment from desktop nav', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav-equipment')
            ->waitForLocation('/equipment')
            ->assertPathIs('/equipment');
    });
});

it('navigates to progress from desktop nav', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav-progress')
            ->waitForLocation('/progress')
            ->assertPathIs('/progress');
    });
});

it('navigates to exercises from mobile bottom nav', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav-exercises')
            ->waitForLocation('/exercises')
            ->assertPathIs('/exercises');
    });
});

it('navigates to equipment from mobile bottom nav', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav-equipment')
            ->waitForLocation('/equipment')
            ->assertPathIs('/equipment');
    });
});

it('navigates to profile from desktop profile link', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-profile-link')
            ->click('@desktop-profile-link')
            ->waitForLocation('/profile')
            ->assertPathIs('/profile');
    });
});

it('navigates to profile from mobile profile link', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-profile-link')
            ->click('@mobile-profile-link')
            ->waitForLocation('/profile')
            ->assertPathIs('/profile');
    });
});

it('navigates to workouts from logo in desktop sidebar', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/exercises')
            ->waitFor('@desktop-nav')
            ->click('@desktop-nav-logo')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});

it('navigates to workouts from logo in mobile top bar', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('@mobile-top-bar')
            ->click('@mobile-top-bar-logo')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});

it('logs out from profile page and redirects to login', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/profile')
            ->waitFor('@logout-btn')
            ->click('@logout-btn')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

it('maintains nav state when navigating between sections at desktop', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@desktop-nav')
            ->assertVisible('@desktop-nav')
            ->click('@desktop-nav-exercises')
            ->waitForLocation('/exercises')
            ->assertVisible('@desktop-nav')
            ->click('@desktop-nav-progress')
            ->waitForLocation('/progress')
            ->assertVisible('@desktop-nav');
    });
});

it('maintains nav state when navigating between sections on mobile', function (): void {
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@mobile-bottom-nav')
            ->assertVisible('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav-exercises')
            ->waitForLocation('/exercises')
            ->assertVisible('@mobile-bottom-nav')
            ->click('@mobile-bottom-nav-equipment')
            ->waitForLocation('/equipment')
            ->assertVisible('@mobile-bottom-nav');
    });
});
