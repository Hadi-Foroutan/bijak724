<?php

namespace App\Services\Permission;

use App\Helpers\ServiceResult;
use App\Interfaces\PermissionInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class PermissionService
{

    public function __construct(
        protected PermissionInterface $permissionRepository,
    )
    {
    }

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

}
