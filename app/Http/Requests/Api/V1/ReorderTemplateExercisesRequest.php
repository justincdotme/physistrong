<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderTemplateExercisesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('template_exercises', 'exercise_id')->where('template_id', $this->route('template')->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // A partial list would assign 0..n-1 to the submitted IDs while
            // unmentioned rows keep their old positions, duplicating orders.
            /** @var WorkoutTemplate $template */
            $template = $this->route('template');

            if (count((array) $this->input('ids')) !== $template->exercises()->count()) {
                $validator->errors()->add('ids', 'The ids list must contain every exercise attached to this template.');
            }
        });
    }
}
