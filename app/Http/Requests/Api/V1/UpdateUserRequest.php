<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\MeasurementSystem;
use App\Enums\ThemePreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$this->user()->id],
            'measurement_system' => ['sometimes', new Enum(MeasurementSystem::class)],
            'theme' => ['sometimes', new Enum(ThemePreference::class)],
            'current_password' => ['required_with:password', 'current_password:api'],
            'password' => ['sometimes', 'string', 'min:6', 'confirmed'],
        ];
    }
}
