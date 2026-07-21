<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutTemplate;

class WorkoutTemplatePolicy
{
    /**
     * @param User            $user
     * @param WorkoutTemplate $template
     *
     * @return boolean
     */
    public function view(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    /**
     * @param User            $user
     * @param WorkoutTemplate $template
     *
     * @return boolean
     */
    public function update(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    /**
     * @param User            $user
     * @param WorkoutTemplate $template
     *
     * @return boolean
     */
    public function delete(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }
}
