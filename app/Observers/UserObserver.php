<?php

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Interfaces\RoleInterface;
use App\Models\User;

class UserObserver
{
    public function __construct(
        protected RoleInterface $roleRepository,
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $role = $this->roleRepository->findByName(RoleEnum::USER->value);
        if ($role) {
            $this->roleRepository->assignRoleToUser($role, $user);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
