<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the login page and checks form elements are visible', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/login')
            ->waitFor('@login-form')
            ->assertSee('Welcome back')
            ->assertSee('Log in to keep your streak going.')
            ->assertPresent('@login-form')
            ->assertPresent('input[name="email"]')
            ->assertPresent('input[name="password"]')
            ->assertSeeLink('Create an account')
            ->assertSeeLink('Forgot password?');
    });
});

it('renders the login page at phone width', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->resize(375, 812)
            ->visit('/login')
            ->waitFor('@login-form')
            ->assertSee('Welcome back')
            ->assertPresent('@login-form')
            ->assertPresent('input[name="email"]')
            ->assertPresent('input[name="password"]')
            ->screenshot('auth-login-phone');
    });
});

it('logs in with valid credentials and redirects to workouts', function (): void {
    $email = 'logintest-' . time() . '@example.com';
    $user  = User::factory()->create([
        'email'    => $email,
        'password' => bcrypt('password123'),
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $browser->visit('/login')
            ->waitFor('@login-form')
            ->type('input[name="email"]', $user->email)
            ->type('input[name="password"]', 'password123')
            ->press('Log In')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});

it('redirects unauthenticated user from workouts to login', function (): void {
    $this->browse(function (Browser $browser): void {
        // The guard redirect now waits on the boot-time auth probe, so give the
        // first cold page load a bigger budget than Dusk's 5-second default.
        $browser->visit('/workouts')
            ->waitForLocation('/login', 10)
            ->assertPathIs('/login');
    });
});

it('renders the register page with measurement system picker', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/register')
            ->assertSee('Create your account')
            ->assertSee('Start tracking every set.')
            ->assertPresent('input[name="first_name"]')
            ->assertPresent('input[name="last_name"]')
            ->assertPresent('input[name="email"]')
            ->assertPresent('input[name="password"]')
            ->assertPresent('input[name="password_confirmation"]')
            ->assertPresent('@measurement-system-picker')
            ->assertSee('Imperial (lb, mi)')
            ->assertSee('Metric (kg, km)')
            ->assertSeeLink('Log in')
            ->screenshot('auth-register-desktop');
    });
});

it('shows error when passwords do not match on register', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/register')
            ->type('input[name="first_name"]', 'Test')
            ->type('input[name="last_name"]', 'User')
            ->type('input[name="email"]', 'test@example.com')
            ->type('input[name="password"]', 'Password123!')
            ->type('input[name="password_confirmation"]', 'DifferentPassword!')
            ->click('@measurement-system-picker button:first-child')
            ->pause(500)
            ->assertDisabled('button[type="submit"]');
    });
});

it('renders the forgot password page', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/password/reset')
            ->assertSee('Reset password')
            ->assertSee("We'll email you a reset link.")
            ->assertPresent('input[name="email"]')
            ->assertSeeLink('Back to login')
            ->screenshot('auth-forgot-password-desktop');
    });
});

it('keeps the auth cookie out of document.cookie and dead after logout', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);

        // HttpOnly plus the /api/v1 path scope keep the token invisible to scripts.
        $cookies = (string) $browser->script('return document.cookie')[0];
        expect($cookies)->not->toContain('ps_token');

        $browser->visit('/profile')
            ->waitFor('@logout-btn')
            ->click('@logout-btn')
            ->waitForLocation('/login')
            ->visit('/workouts')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});
