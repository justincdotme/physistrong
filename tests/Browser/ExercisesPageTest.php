<?php

declare(strict_types=1);

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Laravel\Dusk\Browser;

it('renders the exercises page and displays system exercises', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
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

it('exercises page renders at tablet width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
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

it('exercises page renders at phone width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('@exercises-page')
            ->assertVisible('@create-exercise-btn')
            ->assertVisible('@exercise-list')
            ->screenshot('exercises-phone');
    });
});

it('search filters exercises by name', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercise-list')
            ->type('@exercise-search', 'Bench')
            ->pause(500)
            ->assertSee('Bench Press')
            ->screenshot('exercises-searched');
    });
});

it('navigates to exercise detail page', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link');
    });
});

it('exercise detail displays at desktop width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link')
            ->screenshot('exercise-detail-desktop');
    });
});

it('exercise detail displays at tablet width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->screenshot('exercise-detail-tablet');
    });
});

it('exercise detail displays at phone width', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            // The sticky header and fixed bottom nav can both overlap the
            // card's click point at phone height; center it between them.
            ->script("document.querySelector('[dusk=\"exercise-list\"] > *:first-child').scrollIntoView({block: 'center'})");
        $browser->pause(300)
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@exercise-name')
            ->assertVisible('@exercise-type')
            ->assertVisible('@progress-link')
            ->screenshot('exercise-detail-phone');
    });
});

it('shows progress link on exercise detail', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@progress-link')
            ->assertSee('View Progress');
    });
});

it('hides delete button on system exercise', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertMissing('@delete-exercise-btn');
    });
});

it('does not show distance unit selector when creating a distance exercise', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercises-page')
            ->press('Create')
            ->waitForText('Create Exercise')
            ->pause(300)
            ->assertMissing('#distance-unit')
            ->assertDontSee('Distance unit')
            ->assertDontSee('Tracks elevation')
            ->screenshot('create-exercise-no-distance-unit');
    });
});

it('shows system badge on system exercise detail', function (): void {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('[dusk="exercise-list"] > *:first-child')
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@system-badge')
            ->assertSee('SYSTEM')
            ->assertMissing('@delete-exercise-btn')
            ->screenshot('system-exercise-badge');
    });
});

it('disables exercise delete button when exercise is in use', function (): void {
    $user = User::factory()->create();

    $exercise = Exercise::create([
        'name'    => 'Test Custom Exercise',
        'type'    => ExerciseType::Resistance,
        'user_id' => $user->id,
    ]);
    $exercise->resistance()->create([
        'bodyweight_base'     => false,
        'allows_added_weight' => true,
        'bilateral'           => true,
    ]);

    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $workout->exercises()->attach($exercise->id, ['exercise_order' => 1]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercises-page')
            ->type('@exercise-search', 'Test Custom Exercise')
            ->pause(500)
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@delete-exercise-btn')
            ->assertDisabled('@delete-exercise-btn')
            ->screenshot('exercise-delete-disabled-in-use');
    });
});

it('enables exercise delete button when exercise is not in use', function (): void {
    $user = User::factory()->create();

    $exercise = Exercise::create([
        'name'    => 'Unused Custom Exercise',
        'type'    => ExerciseType::Resistance,
        'user_id' => $user->id,
    ]);
    $exercise->resistance()->create([
        'bodyweight_base'     => false,
        'allows_added_weight' => true,
        'bilateral'           => true,
    ]);

    $this->browse(function (Browser $browser) use ($user): void {
        $this->loginAs($browser, $user);
        $browser->visit('/exercises')
            ->waitFor('@exercises-page')
            ->type('@exercise-search', 'Unused Custom Exercise')
            ->pause(500)
            ->click('[dusk="exercise-list"] > *:first-child')
            ->waitFor('@exercise-detail-page')
            ->assertVisible('@delete-exercise-btn')
            ->assertEnabled('@delete-exercise-btn')
            ->screenshot('exercise-delete-enabled');
    });
});
