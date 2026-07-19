<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Laravel\Dusk\Browser;

it('renders the progress page at desktop width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-desktop');
    });
});

it('renders the progress page at tablet width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-tablet');
    });
});

it('renders the progress page at phone width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/progress')
            ->waitFor('@progress-page')
            ->assertVisible('@progress-page')
            ->assertVisible('@exercise-picker')
            ->screenshot('progress-phone');
    });
});

it('defaults to the first exercise with logged data', function (): void {
    $user = User::factory()->create();

    $exercise = Exercise::where('name', 'Barbell Squat')->whereNull('user_id')->first();

    $workout = Workout::create([
        'user_id' => $user->id,
        'name'    => 'Test Workout',
        'date'    => '2026-06-15',
    ]);
    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);
    $entry->loadMetric()->create(['actual_weight' => 135, 'bodyweight_only' => false]);
    $entry->repMetric()->create(['actual_reps' => 5]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/progress')
            ->waitFor('@progress-page')
            ->waitFor('@searchable-select-trigger')
            ->pause(1000)
            ->assertSeeIn('@searchable-select-trigger', 'Barbell Squat')
            ->screenshot('progress-smart-default');
    });
});

it('shows "no data yet" for exercises without logged entries', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/progress')
            ->waitFor('@progress-page')
            ->click('@searchable-select-trigger')
            ->waitFor('@searchable-select-search-input')
            ->waitForText('no data yet')
            ->screenshot('progress-no-data-indicator');
    });
});

it('filters exercises when typing in the search field', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/progress')
            ->waitFor('@progress-page')
            ->click('@searchable-select-trigger')
            ->waitFor('@searchable-select-search-input')
            ->type('@searchable-select-search-input', 'Barbell Squat')
            ->pause(300)
            ->assertSee('Barbell Squat')
            ->assertDontSee('Plank')
            ->screenshot('progress-search-filter');
    });
});

it('updates the chart when selecting an exercise from search', function (): void {
    $user = User::factory()->create();

    $exercise = Exercise::where('name', 'Dumbbell Bench Press')->whereNull('user_id')->first();
    $workout  = Workout::create([
        'user_id' => $user->id,
        'name'    => 'Bench Day',
        'date'    => '2026-06-20',
    ]);
    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);
    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);
    $entry->loadMetric()->create(['actual_weight' => 185, 'bodyweight_only' => false]);
    $entry->repMetric()->create(['actual_reps' => 8]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/progress')
            ->waitFor('@progress-page')
            ->waitFor('@searchable-select-trigger')
            ->pause(1000)
            ->click('@searchable-select-trigger')
            ->waitFor('@searchable-select-search-input')
            ->type('@searchable-select-search-input', 'Dumbbell Bench Press')
            ->pause(300)
            ->press('Dumbbell Bench Press')
            ->pause(500)
            ->assertSeeIn('@searchable-select-trigger', 'Dumbbell Bench Press')
            ->waitFor('@progress-chart')
            ->screenshot('progress-search-select');
    });
});

it('shows the time range label on the progress page', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/progress')
            ->waitFor('@progress-page')
            ->waitFor('@time-range-selector')
            ->assertSeeIn('@time-range-selector', 'TIME RANGE')
            ->screenshot('progress-time-range-label');
    });
});

it('shows the time range label on the exercise progress page', function (): void {
    $user     = User::factory()->create();
    $exercise = Exercise::where('name', 'Barbell Squat')->whereNull('user_id')->first();

    $this->browse(function (Browser $browser) use ($user, $exercise): void {
        $this->loginAs($browser, $user);
        $browser->visit("/exercises/{$exercise->id}/progress")
            ->waitFor('@exercise-progress-page')
            ->waitFor('@time-range-selector')
            ->assertSeeIn('@time-range-selector', 'TIME RANGE')
            ->screenshot('exercise-progress-time-range-label');
    });
});
