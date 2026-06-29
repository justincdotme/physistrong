<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Laravel\Dusk\Browser;

it('renders the workouts page at desktop width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
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

it('renders the workouts page at tablet width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Workouts')
            ->screenshot('workouts-tablet');
    });
});

it('renders the workouts page at phone width', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
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

it('shows empty state when no workouts exist', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertPresent('@workouts-empty')
            ->assertSee('Ready to train?')
            ->assertSee('Create your first workout or set up a template.')
            ->screenshot('workouts-empty-state');
    });
});

it('shows workout count when workouts exist', function () {
    $user = User::factory()->create();
    Workout::factory(3)->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('3 logged')
            ->screenshot('workouts-with-count');
    });
});

it('displays workout history list', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Chest Day',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('Chest Day')
            ->screenshot('workouts-history-list');
    });
});

it('displays multiple workouts in history', function () {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Leg Day',
    ]);
    Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Back Day',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->assertSee('Leg Day')
            ->assertSee('Back Day')
            ->screenshot('workouts-multiple-history');
    });
});

it('has create workout button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertPresent('@create-workout-btn')
            ->assertSee('New Workout')
            ->screenshot('workouts-create-btn');
    });
});

it('has view progress button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('View Progress')
            ->screenshot('workouts-view-progress-btn');
    });
});

it('has create template button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertSee('Create Template')
            ->screenshot('workouts-create-template-btn');
    });
});

it('shows templates section when templates exist', function () {
    $user = User::factory()->create();
    WorkoutTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Upper Body',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@template-section')
            ->assertSee('Templates')
            ->assertSee('Upper Body')
            ->screenshot('workouts-templates-section');
    });
});

it('hides templates section when no templates exist', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->assertMissing('@template-section')
            ->screenshot('workouts-no-templates');
    });
});

it('displays multiple templates in carousel', function () {
    $user = User::factory()->create();
    WorkoutTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Push Day',
    ]);
    WorkoutTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Pull Day',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@template-section')
            ->assertSee('Push Day')
            ->assertSee('Pull Day')
            ->screenshot('workouts-multiple-templates');
    });
});

it('redirects unauthenticated user from workouts to login', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/workouts')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

it('navigates to workout detail when clicking a workout card', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Workout',
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->click('@workout-history .cursor-pointer:first-child')
            ->waitForLocation("/workouts/{$workout->id}")
            ->assertPathIs("/workouts/{$workout->id}");
    });
});

it('navigates to template detail when clicking a template', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Template',
    ]);

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@template-section')
            ->click('@template-section .cursor-pointer:first-child')
            ->waitForLocation("/templates/{$template->id}")
            ->assertPathIs("/templates/{$template->id}");
    });
});

it('opens create template sheet when clicking create template button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('Create Template')
            ->waitForText('Create Template')
            ->assertSeeIn('h2', 'Create Template')
            ->screenshot('workouts-template-sheet-open');
    });
});

it('closes template sheet when clicking outside', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('Create Template')
            ->waitForText('Create Template')
            ->keys('@keydown', '{Escape}')
            ->waitUntilMissing('.sheet')
            ->screenshot('workouts-template-sheet-closed');
    });
});

it('creates a template with name', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('Create Template')
            ->waitForText('Create Template')
            ->type('#template-name-input', 'New Template')
            ->pause(500)
            ->press('Create')
            ->waitForLocation('/templates')
            ->screenshot('workouts-template-created');
    });

    $this->assertDatabaseHas('workout_templates', [
        'user_id' => $user->id,
        'name' => 'New Template',
    ]);
});

it('disables create template button when name is empty', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('Create Template')
            ->waitForText('Create Template')
            ->assertDisabled('button[type="submit"]')
            ->type('#template-name-input', 'Test')
            ->pause(300)
            ->assertEnabled('button[type="submit"]')
            ->screenshot('workouts-template-button-states');
    });
});

it('navigates to view progress page when clicking view progress button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workouts-page')
            ->press('View Progress')
            ->waitForLocation('/progress')
            ->assertPathIs('/progress');
    });
});

it('shows workout date in history', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'date' => '2024-06-15',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->screenshot('workouts-with-date');
    });
});

it('shows completion status for completed workouts', function () {
    $user = User::factory()->create();
    Workout::factory()->create([
        'user_id' => $user->id,
        'name' => 'Complete Workout',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/workouts')
            ->waitFor('@workout-history')
            ->screenshot('workouts-completion-status');
    });
});
