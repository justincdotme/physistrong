<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exercise $exercise): bool
    {
        return $exercise->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Exercise $exercise): Response
    {
        if ($exercise->user_id === null) {
            return Response::deny('System exercises cannot be modified.');
        }

        return $exercise->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }

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
