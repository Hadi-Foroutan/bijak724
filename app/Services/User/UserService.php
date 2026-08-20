<?php

namespace App\Services\User;

use App\Enums\RoleEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserService
{
    public function __construct(
        protected UserInterface $userRepository,
        protected RoleInterface $roleRepository,
    )
    {
    }

    public function all(array $params): ServiceResult
    {
        return ServiceResult::success($this->userRepository->all($params));
    }

    public function store(array $data): ServiceResult
    {
        $role = $this->roleRepository->findById($data['role_id']);

        if (! $role) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش'])
            );
        }

        $isAdmin = in_array($role->name, [
            RoleEnum::ADMIN->value,
            RoleEnum::SUPERADMIN->value,
        ], true);

        if (! $isAdmin && empty($data['company_id'])) {
            return ServiceResult::error(
                __('validation.required', [
                    'attribute' => 'شرکت'
                ]),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = $this->userRepository->store($data);

        // if has image

        return ServiceResult::success($user);
    }

    public function update(array $data, User $user = null): ServiceResult
    {
        return DB::transaction(function () use ($data, $user) {

            $user ??= auth()->user();

            $role = $this->roleRepository->findById($data['role_id']);

            if (! $role) {
                return ServiceResult::error(
                    __('public.not_found', ['attribute' => 'نقش']),
                    Response::HTTP_NOT_FOUND
                );
            }

            $isAdmin = in_array(
                $role->name,
                [
                    RoleEnum::ADMIN->value,
                    RoleEnum::SUPERADMIN->value,
                ],
                true
            );

            if (! $isAdmin && empty($data['company_id'])) {
                return ServiceResult::error(
                    __('validation.required', ['attribute' => 'شرکت']),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            if ($isAdmin) {
                $data['company_id'] = $data['company_id'] ?? null;
            }

            $user = $this->userRepository->update($data, $user);

            $this->roleRepository->assignRoleToUser($role, $user);

            $this->roleRepository->syncDefaultPermissionsToUser(
                $user,
                $role
            );

            $user->load('roles');

            return ServiceResult::success($user);
        });
    }

    public function destroy(User $user = null): ServiceResult
    {
        if (is_null($user))
            $user = auth()->user();

        // if has image delete

        $this->userRepository->destroy($user);
        return ServiceResult::success(__('public.delete_success', ['attribute' => 'کاربر']));
    }
}
