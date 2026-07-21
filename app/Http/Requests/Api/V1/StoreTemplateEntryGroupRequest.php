<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateEntryGroupRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                           => ['sometimes', 'nullable', 'string', 'max:255'],
            'planned_rounds'                 => ['sometimes', 'integer', 'min:1'],
            'rest_between_exercises_seconds' => ['sometimes', 'integer', 'min:0'],
            'rest_between_rounds_seconds'    => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
