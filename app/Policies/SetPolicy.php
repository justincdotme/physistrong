<?php

namespace App\Policies;

use App\Core\Set;
use App\Core\User;
use App\Core\Workout;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create sets.
     *
     * @param  \App\Core\User  $user
     * @return mixed
     */
    public function create(User $user, Workout $workout)
    {
        return (auth()->user()->id === $workout->user->id);
    }

    /**
     * Determine whether the user can update the set.
     *
     * @param  \App\Core\User  $user
     * @param  \App\Set  $set
     * @return mixed
     */
    public function update(User $user, Set $set)
    {
        return ($user->id === $set->workout->user->id);
    }
}
