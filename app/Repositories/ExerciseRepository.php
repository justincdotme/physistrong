<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExerciseRepository
{
    /**
     * @param User                 $user
     * @param array<string, mixed> $validated
     *
     * @return Exercise
     */
    public function create(User $user, array $validated): Exercise
    {
        return DB::transaction(function () use ($user, $validated) {
            $exercise = Exercise::create([
                'name'              => $validated['name'],
                'type'              => $validated['type'],
                'user_id'           => $user->id,
                'equipment_type_id' => $validated['equipment_type_id'] ?? null,
                'notes'             => $validated['notes'] ?? null,
            ]);

            $exercise->{$exercise->type->childRelation()}()
                ->create($validated['type_attributes'] ?? []);

            return $exercise;
        });
    }

    /**
     * @param Exercise             $exercise
     * @param array<string, mixed> $validated
     *
     * @return Exercise
     */
    public function update(Exercise $exercise, array $validated): Exercise
    {
        return DB::transaction(function () use ($exercise, $validated) {
            $exercise->update(
                collect($validated)->only(['name', 'equipment_type_id', 'notes'])->all(),
            );

            if (isset($validated['type_attributes'])) {
                $exercise->{$exercise->type->childRelation()}()
                    ->updateOrCreate([], $validated['type_attributes']);
            }

            return $exercise;
        });
    }
}
