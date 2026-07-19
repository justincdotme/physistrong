<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Laravel\Dusk\Browser;

it('renders the workout detail page at desktop width', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertVisible('@workout-detail-page')
            ->screenshot('workout-detail-desktop');
    });
});

it('renders the workout detail page at tablet width', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertVisible('@workout-detail-page')
            ->screenshot('workout-detail-tablet');
    });
});

it('renders the workout detail page at phone width', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertVisible('@workout-detail-page')
            ->screenshot('workout-detail-phone');
    });
});

it('displays workout name', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Leg Day Workout',
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertVisible('@workout-name')
            ->assertSeeIn('@workout-name', 'Leg Day Workout');
    });
});

it('displays add exercise button', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@add-exercise-btn')
            ->assertVisible('@add-exercise-btn')
            ->assertSee('Add Exercise');
    });
});

it('displays exercises attached to workout', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $exercise = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);

    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);

    $entry->loadMetric()->create([
        'actual_weight'   => 225,
        'bodyweight_only' => false,
    ]);
    $entry->repMetric()->create([
        'actual_reps' => 5,
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@exercise-section')
            ->assertVisible('@exercise-section')
            ->assertSeeIn('@exercise-section', 'Barbell Squat');
    });
});

it('displays multiple exercises in workout', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $squat = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);
    $bench = Exercise::factory()->resistance()->create(['name' => 'Bench Press']);

    $workout->exercises()->attach($squat->id, ['exercise_order' => 1]);
    $workout->exercises()->attach($bench->id, ['exercise_order' => 2]);

    $entry1 = $workout->entries()->create([
        'exercise_id' => $squat->id,
        'set_order'   => 0,
    ]);
    $entry1->loadMetric()->create([]);
    $entry1->repMetric()->create([]);

    $entry2 = $workout->entries()->create([
        'exercise_id' => $bench->id,
        'set_order'   => 1,
    ]);
    $entry2->loadMetric()->create([]);
    $entry2->repMetric()->create([]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@exercise-section')
            ->assertPresent('@exercise-section')
            ->assertSee('Barbell Squat')
            ->assertSee('Bench Press');
    });
});

it('displays entry rows with metric inputs for resistance exercises', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $exercise = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);

    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);

    $entry->loadMetric()->create([
        'target_weight' => 225,
        'actual_weight' => null,
    ]);
    $entry->repMetric()->create([
        'target_reps' => 5,
        'actual_reps' => null,
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@entry-row')
            ->assertVisible('@entry-row');
    });
});

it('displays entry rows for timed hold exercises', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $exercise = Exercise::factory()->timedHold()->create(['name' => 'Plank Hold']);

    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);

    $entry->durationMetric()->create([
        'target_duration_seconds' => 60,
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@entry-row')
            ->assertVisible('@entry-row')
            ->assertSee('Plank Hold');
    });
});

it('back button navigates to workouts list', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->click('@back')
            ->waitFor('@workouts-page')
            ->assertPresent('@workouts-page');
    });
});

it('metric inputs are accessible at phone width', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $exercise = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);

    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);

    $entry->loadMetric()->create([]);
    $entry->repMetric()->create([]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit("/workouts/{$workout->id}")
            ->waitFor('@entry-row')
            ->assertVisible('@entry-row')
            ->screenshot('workout-detail-phone-entries');
    });
});

it('displays entry group for superset exercises', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $squat = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);
    $bench = Exercise::factory()->resistance()->create(['name' => 'Bench Press']);

    $workout->exercises()->attach($squat->id, ['exercise_order' => 1]);
    $workout->exercises()->attach($bench->id, ['exercise_order' => 2]);

    $group = $workout->groups()->create([
        'name'                           => 'Lower Power',
        'planned_rounds'                 => 3,
        'rest_between_exercises_seconds' => 60,
        'rest_between_rounds_seconds'    => 180,
    ]);

    $entry1 = $workout->entries()->create([
        'exercise_id'    => $squat->id,
        'entry_group_id' => $group->id,
        'group_round'    => 1,
        'set_order'      => 0,
    ]);
    $entry1->loadMetric()->create([]);
    $entry1->repMetric()->create([]);

    $entry2 = $workout->entries()->create([
        'exercise_id'    => $bench->id,
        'entry_group_id' => $group->id,
        'group_round'    => 1,
        'set_order'      => 1,
    ]);
    $entry2->loadMetric()->create([]);
    $entry2->repMetric()->create([]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@entry-group')
            ->assertVisible('@entry-group')
            ->assertSeeIn('@entry-group', 'Lower Power');
    });
});

it('displays workout date', function (): void {
    $user     = User::factory()->create();
    $testDate = '2026-06-15';
    $workout  = Workout::factory()->create([
        'user_id' => $user->id,
        'date'    => $testDate,
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout, $testDate): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertValue('input[type="date"]', $testDate);
    });
});

it('displays completion percentage', function (): void {
    $user    = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $exercise = Exercise::factory()->resistance()->create(['name' => 'Barbell Squat']);

    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $entry = $workout->entries()->create([
        'exercise_id' => $exercise->id,
        'set_order'   => 0,
    ]);

    $entry->loadMetric()->create([
        'actual_weight' => 225,
    ]);
    $entry->repMetric()->create([
        'actual_reps' => 5,
    ]);

    $this->browse(function (Browser $browser) use ($user, $workout): void {
        $this->loginAs($browser, $user);
        $browser->visit("/workouts/{$workout->id}")
            ->waitFor('@workout-detail-page')
            ->assertSee('% complete');
    });
});
