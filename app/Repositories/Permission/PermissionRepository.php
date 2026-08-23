<?php

namespace App\Repositories\Permission;

use App\Interfaces\PermissionInterface;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class PermissionRepository implements PermissionInterface
{
    public function all(array $params): Collection
    {
        return PermissionGroup::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('permissions.id')])
            ->orderBy('id')
            ->get()
            ->each(function (PermissionGroup $permissionGroup): void {
                $permissionGroup->permissions->makeHidden('pivot');
            });
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);
        return $permission->fresh();
    }

    public function destroy(Permission $permission): void
    {
        $permission->delete();
    }

    public function byRole(Role $role): Collection
    {
        return $role->permissions()->get();
    }

    public function findRole($user)
    {
        return $user->roles()->first();
    }

    public function findByName($name): ?Role
    {
        return Role::whereName($name)->first();
    }

    public function syncWithUser(User $user, array $permissions): void
    {
        $user->permissions()->sync($permissions);
    }
}
