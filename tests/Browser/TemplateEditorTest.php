<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WorkoutTemplate;
use Laravel\Dusk\Browser;

it('renders the template editor page at desktop width', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create();

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->resize(1920, 1080)
            ->visit("/templates/{$template->id}")
            ->waitFor('@template-editor-page')
            ->assertVisible('@template-editor-page')
            ->assertVisible('@template-name')
            ->assertVisible('@add-template-exercise-btn')
            ->screenshot('template-editor-desktop');
    });
});

it('renders the template editor page at tablet width', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create();

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->resize(768, 1024)
            ->visit("/templates/{$template->id}")
            ->waitFor('@template-editor-page')
            ->assertVisible('@template-editor-page')
            ->assertVisible('@template-name')
            ->assertVisible('@add-template-exercise-btn')
            ->screenshot('template-editor-tablet');
    });
});

it('renders the template editor page at phone width', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create();

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->resize(375, 812)
            ->visit("/templates/{$template->id}")
            ->waitFor('@template-editor-page')
            ->assertVisible('@template-editor-page')
            ->assertVisible('@template-name')
            ->assertVisible('@add-template-exercise-btn')
            ->screenshot('template-editor-phone');
    });
});

it('displays template name inline edit control', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper Body']);

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->visit("/templates/{$template->id}")
            ->waitFor('@template-name')
            ->assertVisible('@template-name')
            ->assertSeeIn('@template-name', 'Upper Body');
    });
});

it('displays add exercise button', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create();

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->visit("/templates/{$template->id}")
            ->waitFor('@add-template-exercise-btn')
            ->assertVisible('@add-template-exercise-btn')
            ->assertSeeIn('@add-template-exercise-btn', 'Add Exercise');
    });
});

it('displays empty state when no exercises', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->for($user)->create();

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->visit("/templates/{$template->id}")
            ->waitFor('@template-editor-page')
            ->assertVisible('@template-editor-page')
            ->assertSee('No exercises yet');
    });
});
