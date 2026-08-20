<?php

namespace App\Services\Permission;

use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Models\Role;
use App\Models\User;

class RoleService
{

    public function __construct(
        protected RoleInterface $roleRepository,
    )
    {
    }

    public function index(array $data): ServiceResult
    {
        $res = $this->roleRepository->all($data);
        return ServiceResult::success($res);
    }

    public function store(array $data): ServiceResult
    {
        if ($this->roleRepository->findByName($data['name']))
            return ServiceResult::error(__('public.already_exists', ['attribute' => 'نقش']));

        $role = $this->roleRepository->create($data);

        $this->roleRepository->syncRolePermissions($role, $data['permissions']);

        return ServiceResult::success($role);
    }

    public function update(Role $role, array $data): ServiceResult
    {
        $existingRole = $this->roleRepository->findByName($data['name']);

        if ($existingRole && $existingRole->id !== $role->id) {
            return ServiceResult::error(__('public.already_exists', ['attribute' => 'نقش']));
        }

        $role = $this->roleRepository->update($role, $data);

        $this->roleRepository->syncRolePermissions($role, $data['permissions']);

        return ServiceResult::success($role);
    }

    public function delete(Role $role): ServiceResult
    {
        $this->roleRepository->delete($role);
        return ServiceResult::success($role);
    }

    public function syncDefaultPermissionsToUser(User $user, Role $role): ServiceResult
    {
        $this->roleRepository->syncDefaultPermissionsToUser($user, $role);

        return ServiceResult::success();
    }
}
