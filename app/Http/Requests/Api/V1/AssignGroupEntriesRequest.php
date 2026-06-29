<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignGroupEntriesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_id' => ['required', 'integer'],
            'entries.*.group_round' => ['required', 'integer', 'min:1'],
        ];
    }
}
