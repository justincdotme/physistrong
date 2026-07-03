<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Concerns;

use App\Enums\ExerciseType;
use App\Enums\MetricDimension;
use Illuminate\Validation\Validator;

trait ValidatesEntryMetrics
{
    abstract protected function metricExerciseType(): ?ExerciseType;

    /** @return array<string, mixed> */
    protected function metricRules(): array
    {
        $rules = [];

        foreach (MetricDimension::cases() as $dimension) {
            $rules["metrics.{$dimension->value}"] = ['sometimes', 'array'];

            foreach ($dimension->rules() as $field => $fieldRules) {
                $rules["metrics.{$dimension->value}.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Enforce only on otherwise-valid requests; a failed exercise_id
            // must not leak another user's exercise type through the message.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->metricExerciseType();
            if ($type === null) {
                return;
            }

            $allowed = $type->allowedMetrics();

            foreach (array_keys((array) $this->input('metrics', [])) as $key) {
                $dimension = MetricDimension::tryFrom((string) $key);

                if ($dimension === null || ! in_array($dimension, $allowed, true)) {
                    $validator->errors()->add(
                        "metrics.{$key}",
                        "The {$key} metric is not allowed for {$type->value} exercises."
                    );
                }
            }
        });
    }
}
