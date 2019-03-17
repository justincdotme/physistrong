<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{
    /**
     * @test
     */
    public function loginTest()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new Login)
                ->assertPathIs('/login')
                ->attemptLogin('info@justinc.me', 'staging')
                ->waitFor('.workout-preview')
                ->assertHasCookie('authentication', false)
                ->assertPathIs('/workouts');
        });
    }
}
