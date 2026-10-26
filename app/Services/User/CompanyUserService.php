<?php

namespace App\Services\User;

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Permission\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CompanyUserService
{
    /** Permissions only an admin may give; they never appear in the company's permission list. */
    private const ADMIN_ONLY_PERMISSIONS = ['profile.*'];

    public function __construct(
        protected UserService       $userService,
        protected UserInterface     $userRepository,
        protected RoleInterface     $roleRepository,
        protected PermissionService $permissionService,
    )
    {
    }

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

        if (!$role) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش کاربران حمل نقل']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return DB::transaction(fn(): ServiceResult => $this->userService->store([
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
        $user = $this->userRepository->findVisibleForCompany($companyId, $userId);

        return $this->permissionService->forUser($user, $this->companyManagerRole(), self::ADMIN_ONLY_PERMISSIONS);
    }

    /**
     * @param array<int, int> $permissions
     */
    public function syncPermissions(int $companyId, int $userId, array $permissions): ServiceResult
    {
        $user = $this->userRepository->findVisibleForCompany($companyId, $userId);
        $managerRole = $this->companyManagerRole();

        // Admin-only permissions are not shown to the company, so saving the list must neither add nor drop them.
        $adminOnly = Permission::query()->get(['id', 'name'])
            ->filter(fn(Permission $permission): bool => Str::is(self::ADMIN_ONLY_PERMISSIONS, $permission->name))
            ->pluck('id');
        $kept = $user->permissions()->whereIn('permissions.id', $adminOnly)->pluck('permissions.id');

        $user->permissions()->sync(
            collect($permissions)->reject(fn(int $id): bool => $adminOnly->contains($id))->merge($kept)->unique()->values()->all(),
        );

        return $this->permissionService->forUser($user, $managerRole, self::ADMIN_ONLY_PERMISSIONS);
    }

    public function destroy(int $companyId, int $userId, int $actorId): ServiceResult
    {
        $user = $this->userRepository->findForCompany($companyId, $userId);

        if ((int)$user->getKey() === $actorId) {
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

        if (!$role) {
            ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش مدیر شرکت']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $role;
    }
}
