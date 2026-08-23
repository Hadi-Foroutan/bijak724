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
use Laravel\Sanctum\PersonalAccessToken;
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

        $company = $user->company_id !== null
            ? $this->companyForUser($user)
            : null;
        $newToken = $this->accessTokenService->create(
            $user,
            $company ? $this->supportTokenService->companyUserTokenName($company) : 'token',
            $company ? $this->supportTokenService->userAbilitiesFor($company) : ['*'],
        );

        return ServiceResult::success([
            'token' => $newToken->plainTextToken,
            ...$this->authenticationContext($user, $newToken->accessToken),
        ]);
    }

    public function loginAsCompany(User $admin, Company $company, int $durationDays): ServiceResult
    {
        if (! $admin->isSuperAdmin() && ! $admin->hasRole(RoleEnum::ADMIN->value)) {
            return ServiceResult::error(__('public.access_denied', ['attribute' => 'شرکت']), Response::HTTP_FORBIDDEN);
        }

        $supportRole = $this->supportTokenService->supportRole();

        if (! $supportRole) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش مدیر شرکت']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $newToken = $this->accessTokenService->create(
            $admin,
            $this->supportTokenService->tokenName($company),
            $this->supportTokenService->abilitiesFor($company),
            now()->addDays($durationDays),
        );

        return ServiceResult::success([
            'token' => $newToken->plainTextToken,
            ...$this->authenticationContext($admin, $newToken->accessToken),
        ]);
    }

    public function checkToken(): ServiceResult
    {
        /** @var User $user */
        $user = auth()->user();

        return ServiceResult::success(
            $this->authenticationContext($user, $this->accessTokenService->current($user))
        );
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

        if ($user->company_id !== null) {
            $this->companyForUser($user);
        }

        return [
            'user' => UserResource::make($user),
            'role' => $role,
            'permissions' => $user->getPermissions(),
        ];
    }

    /**
     * Build the shared response contract used by login, login-as, and check-token.
     *
     * @return array<string, mixed>
     */
    private function authenticationContext(User $user, ?PersonalAccessToken $token): array
    {
        $data = $this->authenticatedUserData($user);

        if (! $token) {
            return [
                ...$data,
                'auth_mode' => 'system',
            ];
        }

        $companyContext = $this->supportTokenService->context($user, $token);

        if (! $companyContext) {
            return [
                ...$data,
                'auth_mode' => 'system',
            ];
        }

        if ($this->supportTokenService->isSupportAccessToken($token)) {
            $data['role'] = $this->supportTokenService->supportRole();
            $data['permissions'] = $this->supportTokenService->permissionsFromToken($token);
            $data['auth_mode'] = 'company_support';
            $data['support_access'] = $companyContext;

            return $data;
        }

        $data['auth_mode'] = 'company';
        $data['company_access'] = $companyContext;

        return $data;
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
