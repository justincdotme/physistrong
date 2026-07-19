<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy
{
    /**
     * @param User     $user
     * @param Exercise $exercise
     *
     * @return boolean
     */
    public function view(User $user, Exercise $exercise): bool
    {
        return $exercise->isVisibleTo($user);
    }

    /**
     * @param User     $user
     * @param Exercise $exercise
     *
     * @return Response
     */
    public function update(User $user, Exercise $exercise): Response
    {
        if ($exercise->user_id === null) {
            return Response::deny('System exercises cannot be modified.');
        }

        return $exercise->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }

    /**
     * @param User     $user
     * @param Exercise $exercise
     *
     * @return Response
     */
    public function delete(User $user, Exercise $exercise): Response
    {
        if ($exercise->user_id === null) {
            return Response::deny('System exercises cannot be deleted.');
        }

        return $exercise->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }
}
