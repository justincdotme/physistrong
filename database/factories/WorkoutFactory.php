<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Workout> */
class WorkoutFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'date' => fake()->date(),
        ];
    }

    public function withExhaustion(int $level = 5): static
    {
        return $this->state(fn () => ['exhaustion' => $level]);
    }

    public function withSoreness(int $level = 5): static
    {
        return $this->state(fn () => ['soreness' => $level]);
    }
}
