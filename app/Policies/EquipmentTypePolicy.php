<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EquipmentTypePolicy
{
    /**
     * @param User          $user
     * @param EquipmentType $equipmentType
     *
     * @return boolean
     */
    public function view(User $user, EquipmentType $equipmentType): bool
    {
        return $equipmentType->isVisibleTo($user);
    }

    /**
     * @param User          $user
     * @param EquipmentType $equipmentType
     *
     * @return Response
     */
    public function update(User $user, EquipmentType $equipmentType): Response
    {
        if ($equipmentType->is_system) {
            return Response::deny('System equipment types cannot be modified.');
        }

        return $equipmentType->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }

    /**
     * @param User          $user
     * @param EquipmentType $equipmentType
     *
     * @return Response
     */
    public function delete(User $user, EquipmentType $equipmentType): Response
    {
        if ($equipmentType->is_system) {
            return Response::deny('System equipment types cannot be deleted.');
        }

        return $equipmentType->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }
}
