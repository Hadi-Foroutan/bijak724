<?php

namespace App\Services\Auth;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Http\Resources\UserResource;
use App\Interfaces\PermissionInterface;
use App\Interfaces\UserInterface;
use App\Models\Company;
use App\Models\InsuranceAgency;
use App\Models\User;
use App\Services\Company\CompanySupportTokenService;
use App\Services\InsurancePanel\InsuranceAccessService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public function __construct(
        protected UserInterface              $authRepository,
        protected PermissionInterface        $permissionRepository,
        protected CompanySupportTokenService $supportTokenService,
        protected AccessTokenService         $accessTokenService,
        protected InsuranceAccessService     $insuranceAccessService,
    )
    {
    }

    public function login(string $username, string $password): ServiceResult
    {
        $user = $this->authRepository->findByUsername($username);
        if (!$user) {
            return ServiceResult::error(__('public.not_found', ['attribute' => 'کاربر']));
        }

        $verify = Hash::check($password, $user->password);
        if (!$verify) {
            return ServiceResult::error(__('public.invalid_credentials'));
        }

        $company = $user->company_id !== null
            ? $this->companyForUser($user)
            : null;
        $isInsuranceUser = $company === null && $this->insuranceAccessService->isInsuranceUser($user);
        $newToken = $this->accessTokenService->create(
            $user,
            match (true) {
                $company !== null => $this->supportTokenService->companyUserTokenName($company),
                $isInsuranceUser => $this->insuranceAccessService->userTokenName($user),
                default => 'token',
            },
            match (true) {
                $company !== null => $this->supportTokenService->userAbilitiesFor($company),
                $isInsuranceUser => $this->insuranceAccessService->userAbilities(),
                default => ['*'],
            },
        );

        return ServiceResult::success([
            'token' => $newToken->plainTextToken,
            ...$this->authenticationContext($user, $newToken->accessToken),
        ]);
    }

    public function loginAsCompany(User $admin, Company $company, int $durationDays): ServiceResult
    {
        if (!$admin->isSuperAdmin() && !$admin->hasRole(RoleEnum::ADMIN->value)) {
            return ServiceResult::error(__('public.access_denied', ['attribute' => 'شرکت']), Response::HTTP_FORBIDDEN);
        }

        $supportRole = $this->supportTokenService->supportRole();

        if (!$supportRole) {
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

    /** An admin's temporary token for the panel of one insurance agency (the agency level of that agency). */
    public function loginAsInsuranceAgency(User $admin, InsuranceAgency $agency, int $durationDays): ServiceResult
    {
        if (!$admin->isSuperAdmin() && !$admin->hasRole(RoleEnum::ADMIN->value)) {
            return ServiceResult::error(__('public.access_denied', ['attribute' => 'پنل بیمه']), Response::HTTP_FORBIDDEN);
        }

        if (!$this->insuranceAccessService->supportRole()) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش نمایندگی بیمه']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $newToken = $this->accessTokenService->create(
            $admin,
            $this->insuranceAccessService->supportTokenName($agency),
            $this->insuranceAccessService->supportAbilities($agency),
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
        if (!$user->isActive()) {
            ServiceResult::error(__('auth.user_inactive'), Response::HTTP_FORBIDDEN);
        }

        $role = $this->permissionRepository->findRole($user);

        if (!$role) {
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

        if (!$token) {
            return [
                ...$data,
                'auth_mode' => 'system',
            ];
        }

        $companyContext = $this->supportTokenService->context($user, $token);

        if (!$companyContext) {
            return $this->insuranceContext($user, $token, $data);
        }

        if ($this->supportTokenService->isSupportAccessToken($token)) {
            $data['role'] = $this->supportTokenService->supportRole();
            $data['permissions'] = $this->supportTokenService->permissionsForToken($token);
            $data['auth_mode'] = 'company_support';
            $data['company_access'] = $companyContext;

            return $data;
        }

        $data['auth_mode'] = 'company';
        $data['company_access'] = $companyContext;

        return $data;
    }

    /**
     * The context of a session without a company: the insurance panel's (`insurance` for a user of the insurance role,
     * `insurance_support` for an admin inside an agency's panel) or, for everyone else, the system one.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function insuranceContext(User $user, PersonalAccessToken $token, array $data): array
    {
        $insuranceAccess = $this->insuranceAccessService->context($user, $token);

        if ($insuranceAccess === null) {
            return [...$data, 'auth_mode' => 'system'];
        }

        if ($insuranceAccess['access_type'] === 'support') {
            $data['role'] = $this->insuranceAccessService->supportRole();
            $data['permissions'] = $insuranceAccess['permissions'];
            $data['auth_mode'] = 'insurance_support';
        } else {
            $data['auth_mode'] = 'insurance';
        }

        $data['insurance_access'] = $insuranceAccess;

        return $data;
    }

    private function companyForUser(User $user): Company
    {
        $company = $user->company;

        if (!$company || $company->status !== StatusEnum::ACTIVE->value) {
            ServiceResult::error(__('public.access_denied', ['attribute' => 'شرکت']), Response::HTTP_FORBIDDEN);
        }

        return $company;
    }
}
