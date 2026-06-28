<?php

namespace App\Providers;

use App\Notifications\WelcomeNotification;
use DateInterval;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Passport::tokensExpireIn(new DateInterval('P30D'));
        Passport::personalAccessTokensExpireIn(new DateInterval('P30D'));

        Event::listen(Registered::class, function (Registered $event): void {
            $event->user->notify(new WelcomeNotification());
        });
    }
}
