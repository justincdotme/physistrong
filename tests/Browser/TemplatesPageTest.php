<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WorkoutTemplate;
use Laravel\Dusk\Browser;

it('shows the empty state when the user has no templates', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@templates-page')
            ->assertSee('No templates yet')
            ->screenshot('templates-empty');
    });
});

it('lists the user templates', function () {
    $user = User::factory()->create();
    WorkoutTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Push Day']);
    WorkoutTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Pull Day']);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@template-list')
            ->assertSee('Push Day')
            ->assertSee('Pull Day')
            ->screenshot('templates-list');
    });
});

it('navigates to the editor when a template is tapped', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Leg Day']);

    $this->browse(function (Browser $browser) use ($user, $template) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@template-list')
            ->click('@template-list .cursor-pointer:first-child')
            ->waitForLocation("/templates/{$template->id}")
            ->assertPathIs("/templates/{$template->id}");
    });
});

it('creates a template and opens the editor', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@templates-page')
            ->click('@create-template-btn')
            ->waitFor('#template-name-input')
            ->type('#template-name-input', 'Conditioning')
            ->click('@submit-template-btn')
            ->waitFor('@template-editor-page')
            ->assertPathBeginsWith('/templates/');
    });

    $this->assertDatabaseHas('workout_templates', [
        'user_id' => $user->id,
        'name' => 'Conditioning',
    ]);
});

it('deletes a template after confirmation', function () {
    $user = User::factory()->create();
    $template = WorkoutTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Retired Day']);

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@template-list')
            ->click('@delete-template-btn')
            ->waitForText('Delete template?')
            ->press('Delete')
            ->waitUntilMissing('@template-list')
            ->assertSee('No templates yet');
    });

    $this->assertDatabaseMissing('workout_templates', [
        'id' => $template->id,
    ]);
});

it('returns to the workouts page via the back button', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $this->loginAs($browser, $user);
        $browser->visit('/templates')
            ->waitFor('@templates-page')
            ->click('@back')
            ->waitForLocation('/workouts')
            ->assertPathIs('/workouts');
    });
});
