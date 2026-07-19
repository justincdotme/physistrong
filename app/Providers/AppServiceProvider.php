<?php

declare(strict_types=1);

namespace App\Providers;

use App\Notifications\WelcomeNotification;
use DateInterval;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Passport::tokensExpireIn(new DateInterval('P30D'));
        Passport::personalAccessTokensExpireIn(new DateInterval('P30D'));

        Event::listen(Registered::class, function (Registered $event): void {
            $event->user->notify(new WelcomeNotification);
        });

        ResetPassword::createUrlUsing(fn (mixed $notifiable, string $token): string => config('app.url') . '/password/reset/' . $token . '?email=' . urlencode($notifiable->getEmailForPasswordReset()));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-forgot', fn (Request $request) => Limit::perHour(3)->by($request->ip()));
    }
}
