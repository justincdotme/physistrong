<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'date'       => ['required', 'date'],
            'exhaustion' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'soreness'   => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
