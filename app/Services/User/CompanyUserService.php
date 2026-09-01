<?php

namespace App\Services\User;

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
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
}
