<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexExerciseRequest extends FormRequest
{
    /** @return boolean */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type'              => ['sometimes', Rule::enum(ExerciseType::class)],
            'equipment_type_id' => ['sometimes', 'integer'],
            'search'            => ['sometimes', 'string', 'max:255'],
        ];
    }
}
