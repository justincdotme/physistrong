<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTemplateGroupExercisesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exercise_ids'   => ['required', 'array', 'min:1'],
            'exercise_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('template_exercises', 'exercise_id')->where('template_id', $this->route('template')->id),
            ],
        ];
    }
}
