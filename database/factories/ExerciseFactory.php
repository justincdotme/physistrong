<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exercise> */
class ExerciseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name'    => fake()->unique()->words(3, true),
            'type'    => ExerciseType::Resistance,
            'user_id' => null,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return static
     */
    public function resistance(array $attributes = []): static
    {
        return $this->state(fn () => ['type' => ExerciseType::Resistance])
            ->afterCreating(fn (Exercise $exercise) => $exercise->resistance()->create($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return static
     */
    public function timedHold(array $attributes = []): static
    {
        return $this->state(fn () => ['type' => ExerciseType::TimedHold])
            ->afterCreating(fn (Exercise $exercise) => $exercise->timedHold()->create($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return static
     */
    public function distance(array $attributes = []): static
    {
        return $this->state(fn () => ['type' => ExerciseType::Distance])
            ->afterCreating(fn (Exercise $exercise) => $exercise->distance()->create($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return static
     */
    public function interval(array $attributes = []): static
    {
        return $this->state(fn () => ['type' => ExerciseType::Interval])
            ->afterCreating(fn (Exercise $exercise) => $exercise->interval()->create($attributes));
    }
}
