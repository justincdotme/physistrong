<?php

namespace App\Policies;

use App\Core\User;
use App\Core\Exercise;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExercisePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the exercise.
     *
     * @param  \App\Core\User $user
     * @param Exercise $exercise
     * @return mixed
     */
    public function view(User $user, Exercise $exercise)
    {
        return ($user->id === $exercise->user->id);
    }

    /**
     * Determine whether the user can create exercises.
     *
     * @param  \App\Core\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return (auth()->user());
    }

    /**
     * Determine whether the user can update the exercise.
     *
     * @param  \App\Core\User $user
     * @param Exercise $exercise
     * @return mixed
     */
    public function update(User $user, Exercise $exercise)
    {
        return ($user->id === $exercise->user->id);
    }
}
