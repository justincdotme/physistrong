<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesPassportToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
        $this->artisan('db:seed', ['--class' => 'EquipmentTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'ExerciseLibrarySeeder']);
    }

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions())->addArguments(array_filter([
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--ignore-certificate-errors',
            env('DUSK_HEADLESS_DISABLED') ? null : '--headless=new',
        ]));

        return RemoteWebDriver::create(
            env('DUSK_DRIVER_URL', 'http://selenium:4444'),
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    protected function loginAs(Browser $browser, User $user, string $password = 'password'): Browser
    {
        return $browser->visit('/login')
            ->waitForText('Welcome back')
            ->type('email', $user->email)
            ->type('password', $password)
            ->press('Log In')
            ->waitFor('@app-shell');
    }
}
