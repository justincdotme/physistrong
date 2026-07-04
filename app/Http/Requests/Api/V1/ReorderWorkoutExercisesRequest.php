<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Workout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderWorkoutExercisesRequest extends FormRequest
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
                Rule::exists('exercise_workout', 'exercise_id')->where('workout_id', $this->route('workout')->id),
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
            /** @var Workout $workout */
            $workout = $this->route('workout');

            if (count((array) $this->input('ids')) !== $workout->exercises()->count()) {
                $validator->errors()->add('ids', 'The ids list must contain every exercise attached to this workout.');
            }
        });
    }
}
