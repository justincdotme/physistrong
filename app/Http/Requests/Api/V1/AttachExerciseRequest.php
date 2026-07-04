<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachExerciseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exercise_id' => [
                'required',
                Rule::exists('exercises', 'id')->where(Exercise::visibilityConstraint($this->user())),
            ],
        ];
    }
}
