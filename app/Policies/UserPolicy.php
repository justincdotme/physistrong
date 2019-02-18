<?php

namespace App\Policies;

use App\Core\User;

use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Core\User - $user The authenticated user
     * @param User $model - The requested resource id
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        return ($user->id === $model->id);
    }
}
