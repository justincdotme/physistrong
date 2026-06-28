<?php

namespace App\Models;

use App\Enums\MeasurementSystem;
use App\Enums\ThemePreference;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'measurement_system', 'theme'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'measurement_system' => MeasurementSystem::class,
            'theme' => ThemePreference::class,
        ];
    }
}
