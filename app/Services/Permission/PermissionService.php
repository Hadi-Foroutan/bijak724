<?php

namespace App\Services\Permission;

use App\Helpers\ServiceResult;
use App\Interfaces\PermissionInterface;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class PermissionService
{
    public function __construct(
        protected PermissionInterface $permissionRepository,
    ) {}

    public function all(array $params): ServiceResult
    {
        return ServiceResult::success($this->permissionRepository->all($params));
    }

    public function create($data): ServiceResult
    {
        $this->permissionRepository->create($data);

        return ServiceResult::success(__('public.created_success', ['attribute' => 'دسترسی']));
    }

    public function update(Permission $permission, $data): ServiceResult
    {
        $this->permissionRepository->update($permission, $data);

        return ServiceResult::success(__('public.update_success', ['attribute' => 'دسترسی']));
    }

    public function destroy(Permission $permission): ServiceResult
    {
        $this->permissionRepository->destroy($permission);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'دسترسی']));
    }

    public function findRole(User $user): ServiceResult
    {
        $role = $this->permissionRepository->findRole($user);

        return ServiceResult::success($role);
    }

    public function syncWithUser(User $user, array $permissions): ServiceResult
    {
        $this->permissionRepository->syncWithUser($user, $permissions);

        return ServiceResult::success(__('public.update_success', ['attribute' => 'دسترسی ها']));
    }

    public function forUser(User $user, ?Role $availableRole = null): ServiceResult
    {
        /** @var Collection<int, Permission> $availablePermissions */
        $availablePermissions = $availableRole === null
            ? Permission::query()->with('groups:id,name')->orderBy('id')->get()
            : $availableRole->permissions()->with('groups:id,name')->orderBy('permissions.id')->get();

        return ServiceResult::success($this->permissionData($user, $availablePermissions));
    }

    /**
     * @param  Collection<int, Permission>  $availablePermissions
     * @return array{user_id: int, permission_ids: array<int, int>, permission_groups: array<int, array<string, mixed>>}
     */
    private function permissionData(User $user, Collection $availablePermissions): array
    {
        $selectedPermissionIds = $user->permissions()
            ->whereIn('permissions.id', $availablePermissions->modelKeys())
            ->pluck('permissions.id')
            ->map(fn (int $permissionId): int => $permissionId)
            ->values();
        $selectedPermissionLookup = $selectedPermissionIds->flip();
        $groups = $availablePermissions
            ->flatMap(fn (Permission $permission): Collection => $permission->groups)
            ->unique('id')
            ->sortBy('id')
            ->values()
            ->map(function (PermissionGroup $group) use ($availablePermissions, $selectedPermissionLookup): array {
                $permissions = $availablePermissions
                    ->filter(fn (Permission $permission): bool => $permission->groups->contains('id', $group->id))
                    ->map(fn (Permission $permission): array => $this->permissionItem(
                        $permission,
                        $selectedPermissionLookup->has($permission->id),
                    ))
                    ->values()
                    ->all();

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'permissions' => $permissions,
                ];
            });
        $groupedPermissionIds = $groups
            ->flatMap(fn (array $group): array => array_column($group['permissions'], 'id'));
        $ungroupedPermissions = $availablePermissions
            ->whereNotIn('id', $groupedPermissionIds)
            ->map(fn (Permission $permission): array => $this->permissionItem(
                $permission,
                $selectedPermissionLookup->has($permission->id),
            ))
            ->values()
            ->all();

        if ($ungroupedPermissions !== []) {
            $groups->push([
                'id' => null,
                'name' => 'سایر دسترسی‌ها',
                'permissions' => $ungroupedPermissions,
            ]);
        }

        return [
            'user_id' => $user->id,
            'permission_ids' => $selectedPermissionIds->all(),
            'permission_groups' => $groups->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, display_name: string, description: ?string, is_selected: bool}
     */
    private function permissionItem(Permission $permission, bool $isSelected): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
            'is_selected' => $isSelected,
        ];
    }
}
