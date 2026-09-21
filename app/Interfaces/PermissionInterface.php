<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

interface PermissionInterface
{
    public function all(array $params): Collection;

    public function create(array $data): Permission;

    public function update(Permission $permission, array $data): Permission;

    public function destroy(Permission $permission);

    public function byRole(Role $role): Collection;

    public function findRole($user): ?Role;

    public function findByName($name);

    public function syncWithUser(User $user, array $permissions);
}
