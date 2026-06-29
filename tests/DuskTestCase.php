<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesPassportToken;

    private const DUSK_DATABASE = 'physistrong_dusk';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (static::$browsers as $browser) {
            try {
                $browser->quit();
            } catch (\Throwable) {
            }
        }
        static::$browsers = [];

        $this->assertDuskDatabase();
        $this->truncateAllTables();
        $this->setUpPassport();
        $this->artisan('db:seed', ['--class' => 'EquipmentTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'ExerciseLibrarySeeder']);
    }

    private function assertDuskDatabase(): void
    {
        $current = config('database.connections.mysql.database');

        if ($current !== self::DUSK_DATABASE) {
            throw new RuntimeException(
                'Dusk tests must run against the test database ({self::DUSK_DATABASE}), '
                ."but the current connection targets \"{$current}\". "
                .'Check .env.dusk.local and verify artisan dusk restored .env after its last run.'
            );
        }
    }

    private function truncateAllTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.self::DUSK_DATABASE;

        foreach ($tables as $table) {
            $name = $table->{$key};
            if ($name !== 'migrations') {
                DB::table($name)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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
