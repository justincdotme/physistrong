<?php

namespace App\Providers;

use App\Core\Set;
use App\Core\User;
use App\Core\Workout;
use App\Core\Exercise;
use App\Policies\SetPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkoutPolicy;
use App\Policies\ExercisePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Workout::class => WorkoutPolicy::class,
        User::class => UserPolicy::class,
        Set::class => SetPolicy::class,
        Exercise::class => ExercisePolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
