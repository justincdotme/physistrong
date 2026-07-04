<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseType;
use App\Http\Requests\Api\V1\Concerns\ValidatesExerciseAttributes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    use ValidatesExerciseAttributes;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exercises')->where('user_id', $this->user()->id),
            ],
            'type' => ['required', Rule::enum(ExerciseType::class)],
            'equipment_type_id' => $this->equipmentTypeRule(),
            'notes' => ['nullable', 'string'],
            'type_attributes' => ['sometimes', 'array'],
        ], $this->typeRulesFor(ExerciseType::tryFrom((string) $this->input('type'))));
    }
}
