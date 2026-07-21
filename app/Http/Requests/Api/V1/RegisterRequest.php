<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\MeasurementSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:6', 'confirmed'],
            'measurement_system' => ['required', new Enum(MeasurementSystem::class)],
            'first_name'         => ['nullable', 'string', 'max:255'],
            'last_name'          => ['nullable', 'string', 'max:255'],
        ];
    }
}
