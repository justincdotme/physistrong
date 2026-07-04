<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignGroupEntriesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('workout_entries', 'id')->where('workout_id', $this->route('workout')->id),
            ],
            'entries.*.group_round' => ['required', 'integer', 'min:1'],
        ];
    }
}
