<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Dusk\Browser;

it('renders the login page and checks form elements are visible', function () {
    $this->browse(function (Browser $browser) {
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

it('renders the login page at phone width', function () {
    $this->browse(function (Browser $browser) {
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

it('logs in with valid credentials and redirects to workouts', function () {
    $email = 'logintest-'.time().'@example.com';
    $user = User::factory()->create([
        'email' => $email,
        'password' => bcrypt('password123'),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitFor('@login-form')
            ->type('input[name="email"]', $user->email)
            ->type('input[name="password"]', 'password123')
            ->press('Log In')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});


it('redirects unauthenticated user from workouts to login', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/workouts')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

it('renders the register page with measurement system picker', function () {
    $this->browse(function (Browser $browser) {
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


it('shows error when passwords do not match on register', function () {
    $this->browse(function (Browser $browser) {
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

it('renders the forgot password page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/password/reset')
            ->assertSee('Reset password')
            ->assertSee("We'll email you a reset link.")
            ->assertPresent('input[name="email"]')
            ->assertSeeLink('Back to login')
            ->screenshot('auth-forgot-password-desktop');
    });
});
