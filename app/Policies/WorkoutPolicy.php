<?php

namespace App\Policies;

use App\Core\User;
use App\Core\Workout;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkoutPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the workout.
     *
     * @param  \App\Core\User  $user
     * @param  \App\Core\Workout  $workout
     * @return mixed
     */
    public function view(User $user, Workout $workout)
    {
        return ($user->id == $workout->user_id);
    }

    /**
     * Determine whether the user can create workouts.
     *
     * @param  \App\Core\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return (auth()->user());
    }

    /**
     * Determine whether the user can update the workout.
     *
     * @param  \App\Core\User  $user
     * @param  \App\Core\Workout  $workout
     * @return mixed
     */
    public function update(User $user, Workout $workout)
    {
        return ($user->id == $workout->user_id);
    }

    /**
     * Determine whether the user can delete the workout.
     *
     * @param  \App\Core\User  $user
     * @param  \App\Core\Workout  $workout
     * @return mixed
     */
    public function delete(User $user, Workout $workout)
    {
        return ($user->id == $workout->user_id);
    }

    /**
     * Determine whether the user can view the workout index.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return auth()->user()->id == $user->id;
    }
}
