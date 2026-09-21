<?php

namespace App\Repositories\Permission;

use App\Interfaces\RoleInterface;
use App\Models\Role;
use App\Models\User;

class RoleRepository implements RoleInterface
{
    public function all(array $params)
    {
        $roles = Role::searchRecords(
            $params,
            fn ($query) => $query->with(['permissions:id']),
        );

        $roles->each(function (Role $role): void {
            $permissionIds = $role->permissions->modelKeys();

            $role->unsetRelation('permissions');
            $role->setAttribute('permissions', $permissionIds);
        });

        return $roles;
    }

    public function findByName(string $name): ?Role
    {
        return Role::query()->where('name', $name)->first();
    }

    public function findById(int $id): ?Role
    {
        return Role::query()->where('id', $id)->first();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): ?Role
    {
        $role->update($data);

        return $role->fresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function assignRoleToUser(Role $role, User $user): void
    {
        $user->roles()->sync([$role->id]);

        if (in_array($role->name, config('permission_groups.default_only_roles', []), true)) {
            $this->syncDefaultPermissionsToUser($user, $role);

            return;
        }

        $this->syncPermissionsToUser($user, $role);
    }

    public function syncDefaultPermissionsToUser(User $user, Role $role): void
    {
        $defaultPermissionIds = $role->permissions()
            ->where('is_default', true)
            ->pluck('permissions.id')
            ->all();

        $user->permissions()->sync($defaultPermissionIds);
    }

    public function syncPermissionsToUser(User $user, Role $role): void
    {
        $permissionIds = $role->permissions()
            ->pluck('permissions.id')
            ->all();

        $user->permissions()->sync($permissionIds);
    }

    public function syncRolePermissions(Role $role, array $permissions): void
    {
        $role->permissions()->sync($permissions);
    }
}
