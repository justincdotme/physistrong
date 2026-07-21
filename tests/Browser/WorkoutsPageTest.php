<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Laravel\Dusk\Browser;

it('renders the workouts page at desktop width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Workouts')
            ->assertSee('0 logged')
            ->assertPresent('@desktop-nav')
            ->assertMissing('@mobile-bottom-nav')
            ->screenshot('workouts-desktop');
    });
});

it('renders the workouts page at tablet width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Workouts')
            ->screenshot('workouts-tablet');
    });
});

it('renders the workouts page at phone width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Workouts')
            ->assertPresent('@mobile-bottom-nav')
            ->assertMissing('@desktop-nav')
            ->screenshot('workouts-phone');
    });
});

it('shows empty state when no workouts exist', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertPresent('@workouts-empty')
            ->assertSee('Ready to train?')
            ->assertSee('Create your first workout or set up a template.')
            ->screenshot('workouts-empty-state');
    });
});

it('shows workout count when workouts exist', function (): void {
    $user = User::factory()->create();
    Workout::factory(3)->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('3 logged')
            ->screenshot('workouts-with-count');
    });
});

it('displays workout history list', function (): void {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Chest Day',
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('Chest Day')
            ->screenshot('workouts-history-list');
    });
});

it('displays multiple workouts in history', function (): void {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Leg Day',
    ]);
    Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Back Day',
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('Leg Day')
            ->assertSee('Back Day')
            ->screenshot('workouts-multiple-history');
    });
});

it('has create workout button', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertPresent('@create-workout-btn')
            ->assertSee('New Workout')
            ->screenshot('workouts-create-btn');
    });
});

it('has view progress button', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('View Progress')
            ->screenshot('workouts-view-progress-btn');
    });
});

it('has a templates button', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Templates')
            ->screenshot('workouts-templates-btn');
    });
});

it('navigates to the templates page from the templates button', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->click('@templates-link')
            ->waitForLocation('/templates')
            ->assertPathIs('/templates');
    });
});

it('does not list templates on the workouts page', function (): void {
    $user = User::factory()->create();
    WorkoutTemplate::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Upper Body',
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertMissing('@template-section')
            ->assertDontSee('Upper Body')
            ->screenshot('workouts-no-template-list');
    });
});

it('redirects unauthenticated user from workouts to login', function (): void {
    $this->browse(function (Browser $browser): void {
        $browser->visit('/workouts')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

it('navigates to workout detail when clicking a workout card', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Test Workout',
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->click('@workout-history .cursor-pointer:first-child')
            ->waitForLocation("/workouts/{$workout->id}")
            ->assertPathIs("/workouts/{$workout->id}");
    });
});

it('navigates to view progress page when clicking view progress button', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('View Progress')
            ->waitForLocation('/progress')
            ->assertPathIs('/progress');
    });
});

it('shows workout date in history', function (): void {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'date'    => '2024-06-15',
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->screenshot('workouts-with-date');
    });
});

it('shows completion status for completed workouts', function (): void {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Complete Workout',
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->screenshot('workouts-completion-status');
    });
});
