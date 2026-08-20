<?php

namespace App\Services\Auth;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Http\Resources\UserResource;
use App\Interfaces\PermissionInterface;
use App\Interfaces\UserInterface;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\CompanySupportTokenService;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public function __construct(
        protected UserInterface $authRepository,
        protected PermissionInterface $permissionRepository,
        protected CompanySupportTokenService $supportTokenService,
        protected AccessTokenService $accessTokenService,
    ) {}

    public function login(string $username, string $password): ServiceResult
    {
        $user = $this->authRepository->findByUsername($username);
        if (! $user) {
            return ServiceResult::error(__('public.not_found', ['attribute' => 'کاربر']));
        }

        $verify = Hash::check($password, $user->password);
        if (! $verify) {
            return ServiceResult::error(__('public.invalid_credentials'));
        }

        $authenticatedUserData = $this->authenticatedUserData($user);
        $company = $authenticatedUserData['role'] === RoleEnum::USER->value
            ? $this->companyForUser($user)
            : null;
        $newToken = $this->accessTokenService->create(
            $user,
            $company ? $this->supportTokenService->companyUserTokenName($company) : 'token',
            $company ? $this->supportTokenService->userAbilitiesFor($company) : ['*'],
        );

        $data = [
            'token' => $newToken->plainTextToken,
            ...$authenticatedUserData,
            'auth_mode' => $company ? 'company' : 'system',
        ];

        if ($company) {
            $data['company'] = $this->supportTokenService->context($user, $newToken->accessToken);
        }

        return ServiceResult::success($data);
    }

    public function loginAsCompany(User $admin, Company $company, int $durationDays): ServiceResult
    {
        if (! $admin->isSuperAdmin() && ! $admin->hasRole(RoleEnum::ADMIN->value)) {
            return ServiceResult::error(__('public.access_denied', ['attribute' => 'شرکت']), Response::HTTP_FORBIDDEN);
        }

        $authenticatedUserData = $this->authenticatedUserData($admin);
        $newToken = $this->accessTokenService->create(
            $admin,
            $this->supportTokenService->tokenName($company),
            $this->supportTokenService->abilitiesFor($company),
            now()->addDays($durationDays),
        );

        return ServiceResult::success([
            'token' => $newToken->plainTextToken,
            ...$authenticatedUserData,
            'auth_mode' => 'company_support',
            //            'support_access' => $this->supportTokenService->context($admin, $newToken->accessToken),
        ]);
    }

    public function checkToken(): ServiceResult
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $this->authenticatedUserData($user);

        $supportAccess = $this->supportTokenService->currentContext($user);
        if ($supportAccess) {
            $isSupportToken = $this->supportTokenService->isSupportToken($user);
            $data['auth_mode'] = $isSupportToken ? 'company_support' : 'company';
            $data[$isSupportToken ? 'support_access' : 'company_access'] = $supportAccess;
        } else {
            $data['auth_mode'] = 'system';
        }

        return ServiceResult::success($data);
    }

    public function logout(): ServiceResult
    {
        /** @var User $user */
        $user = auth()->user();
        $this->accessTokenService->revokeCurrent($user);

        return ServiceResult::success(__('user.logout'));
    }

    /**
     * @return array{user: UserResource, role: string, permissions: array<int, string>}
     */
    private function authenticatedUserData(User $user): array
    {
        if (! $user->isActive()) {
            ServiceResult::error(__('auth.user_inactive'), Response::HTTP_FORBIDDEN);
        }

        $role = $this->permissionRepository->findRole($user);

        if (! $role) {
            ServiceResult::error(__('public.not_found', ['attribute' => 'نقش']), Response::HTTP_FORBIDDEN);
        }

        if ($role->name === RoleEnum::USER->value) {
            $this->companyForUser($user);
        }

        return [
            'user' => UserResource::make($user),
            'role' => $role->name,
            'permissions' => $user->getPermissions(),
        ];
    }

    private function companyForUser(User $user): Company
    {
        $company = $user->company;

        if (! $company || $company->status !== StatusEnum::ACTIVE->value) {
            ServiceResult::error(__('public.access_denied', ['attribute' => 'شرکت']), Response::HTTP_FORBIDDEN);
        }

        return $company;
    }
}
