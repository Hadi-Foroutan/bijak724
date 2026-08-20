<?php

namespace App\Interfaces;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

interface RoleInterface
{
    public function all(array $params);

    public function create(array $data): Role;

    public function findByName(string $name): ?Role;

    public function findById(int $id): ?Role;

    public function update(Role $role,array $data): ?Role;

    public function delete(Role $role): void;

    public function assignRoleToUser(Role $role, User $user);

    public function syncDefaultPermissionsToUser(User $user, Role $role): void;

    public function syncPermissionsToUser(User $user, Role $role): void;

    public function syncRolePermissions(Role $role, array $permissions);
}
