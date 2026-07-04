<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExerciseRepository
{
    /** @param  array<string, mixed>  $validated */
    public function create(User $user, array $validated): Exercise
    {
        return DB::transaction(function () use ($user, $validated) {
            $exercise = Exercise::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'user_id' => $user->id,
                'equipment_type_id' => $validated['equipment_type_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $exercise->{$exercise->type->childRelation()}()
                ->create($validated['type_attributes'] ?? []);

            return $exercise;
        });
    }

    /** @param  array<string, mixed>  $validated */
    public function update(Exercise $exercise, array $validated): Exercise
    {
        return DB::transaction(function () use ($exercise, $validated) {
            $exercise->update(
                collect($validated)->only(['name', 'equipment_type_id', 'notes'])->all()
            );

            if (isset($validated['type_attributes'])) {
                $exercise->{$exercise->type->childRelation()}
                    ->update($validated['type_attributes']);
            }

            return $exercise;
        });
    }

    /** @return array<string, mixed>|null */
    public function typeAttributes(Exercise $exercise): ?array
    {
        $child = $exercise->{$exercise->type->childRelation()};

        if ($child === null) {
            return null;
        }

        return match ($exercise->type) {
            ExerciseType::Resistance => [
                'bodyweight_base' => $child->bodyweight_base,
                'allows_added_weight' => $child->allows_added_weight,
                'bilateral' => $child->bilateral,
            ],
            ExerciseType::TimedHold => [
                'target_duration_seconds' => $child->target_duration_seconds,
            ],
            ExerciseType::Distance => [
                'tracks_elevation' => $child->tracks_elevation,
            ],
            ExerciseType::Interval => [
                'default_work_seconds' => $child->default_work_seconds,
                'default_rest_seconds' => $child->default_rest_seconds,
                'default_rounds' => $child->default_rounds,
            ],
        };
    }
}
