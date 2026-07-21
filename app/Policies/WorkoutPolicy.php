<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    /**
     * @param User    $user
     * @param Workout $workout
     *
     * @return boolean
     */
    public function view(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    /**
     * @param User    $user
     * @param Workout $workout
     *
     * @return boolean
     */
    public function update(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    /**
     * @param User    $user
     * @param Workout $workout
     *
     * @return boolean
     */
    public function delete(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }
}
