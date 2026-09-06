<?php

namespace App\Services\User;

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CompanyUserService
{
    public function __construct(
        protected UserService $userService,
        protected UserInterface $userRepository,
        protected RoleInterface $roleRepository,
    ) {}

    public function all(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success(
            $this->userRepository->allForCompany($companyId, $params),
        );
    }

    public function tree(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success(
            $this->userRepository->treeForCompany($companyId, $params),
        );
    }

    public function store(int $companyId, array $data): ServiceResult
    {
        $role = $this->roleRepository->findByName(RoleEnum::USER->value);

        if (! $role) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش پیش‌فرض کاربر']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return DB::transaction(fn (): ServiceResult => $this->userService->store([
            ...$data,
            'company_id' => $companyId,
            'role_id' => $role->id,
            'status' => $data['status'] ?? UserStatusEnum::ACTIVE->value,
        ]));
    }

    public function find(int $companyId, int $userId): ServiceResult
    {
        return ServiceResult::success(
            $this->userRepository->findForCompany($companyId, $userId),
        );
    }

    public function update(int $companyId, int $userId, array $data): ServiceResult
    {
        $user = $this->userRepository->findForCompany($companyId, $userId);

        return $this->userService->update($data, $user);
    }

    public function permissions(int $companyId, int $userId): ServiceResult
    {
        $user = $this->userRepository->findForCompany($companyId, $userId);

        return ServiceResult::success(
            $this->permissionData($user, $this->companyManagerRole()),
        );
    }

    /**
     * @param  array<int, int>  $permissions
     */
    public function syncPermissions(int $companyId, int $userId, array $permissions): ServiceResult
    {
        $user = $this->userRepository->findForCompany($companyId, $userId);
        $managerRole = $this->companyManagerRole();

        $user->permissions()->sync($permissions);

        return ServiceResult::success($this->permissionData($user, $managerRole));
    }

    public function destroy(int $companyId, int $userId, int $actorId): ServiceResult
    {
        $user = $this->userRepository->findForCompany($companyId, $userId);

        if ((int) $user->getKey() === $actorId) {
            return ServiceResult::error(
                __('public.access_denied', ['attribute' => 'حذف حساب کاربری خود']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->userService->destroy($user);
    }

    private function companyManagerRole(): Role
    {
        $role = $this->roleRepository->findByName(RoleEnum::COMPANY_MANAGER->value);

        if (! $role) {
            ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش مدیر شرکت']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $role;
    }

    /**
     * @return array{user_id: int, permission_ids: array<int, int>, permission_groups: array<int, array<string, mixed>>}
     */
    private function permissionData(User $user, Role $managerRole): array
    {
        /** @var Collection<int, Permission> $availablePermissions */
        $availablePermissions = $managerRole->permissions()
            ->with('groups:id,name')
            ->orderBy('permissions.id')
            ->get();
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
     * @return array{id: int, name: string, display_name: string, description: ?string, is_default: bool, is_selected: bool}
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
