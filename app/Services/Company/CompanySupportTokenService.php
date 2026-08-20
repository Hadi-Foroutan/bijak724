<?php

namespace App\Services\Company;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use App\Services\Auth\AccessTokenService;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class CompanySupportTokenService
{
    public const DEFAULT_DURATION = 1;

    private const COMPANY_USER_ABILITY = 'company-user';

    private const SUPPORT_ABILITY = 'company-support';

    public function __construct(
        protected AccessTokenService $accessTokenService,
    ) {}

    public function tokenName(Company $company): string
    {
        return "company-support:{$company->id}:".Str::uuid();
    }

    public function companyUserTokenName(Company $company): string
    {
        return "company-user:{$company->id}:".Str::uuid();
    }

    /**
     * @return array<int, string>
     */
    public function abilitiesFor(Company $company): array
    {
        return [self::SUPPORT_ABILITY, $this->companyAbility($company->id)];
    }

    /**
     * @return array<int, string>
     */
    public function userAbilitiesFor(Company $company): array
    {
        return [self::COMPANY_USER_ABILITY, $this->companyAbility($company->id)];
    }

    public function isSupportToken(User $user): bool
    {
        return $this->supportToken($user) !== null;
    }

    public function isCompanyToken(User $user): bool
    {
        return $this->companyId($user) !== null;
    }

    public function companyId(User $user): ?int
    {
        $token = $this->accessTokenService->current($user);

        if (! $token) {
            return null;
        }

        return $this->companyIdFromToken($token);
    }

    public function currentContext(User $user): ?array
    {
        $token = $this->accessTokenService->current($user);

        return $token && $this->companyIdFromToken($token)
            ? $this->context($user, $token)
            : null;
    }

    private function supportToken(User $user): ?PersonalAccessToken
    {
        $token = $this->accessTokenService->current($user);

        if (! $token) {
            return null;
        }

        return $this->accessTokenService->hasAbility($token, self::SUPPORT_ABILITY)
            ? $token
            : null;
    }

    public function context(User $admin, PersonalAccessToken $token): ?array
    {
        $companyId = $this->companyIdFromToken($token);
        $company = $companyId ? Company::query()->find($companyId) : null;

        if (! $company) {
            return null;
        }

        return [
            'access_type' => $this->accessTokenService->hasAbility($token, self::SUPPORT_ABILITY)
                ? 'support'
                : 'company_user',
            'company' => CompanyResource::make($company)->resolve(),
            'issued_by' => [
                'id' => $admin->id,
                'full_name' => $admin->full_name,
            ],
            'expires_at' => $token->expires_at?->toIso8601String(),
        ];
    }

    private function companyIdFromToken(PersonalAccessToken $token): ?int
    {
        foreach ($this->accessTokenService->abilities($token) as $ability) {
            if (preg_match('/^company:(\d+)$/', $ability, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function companyAbility(int $companyId): string
    {
        return "company:{$companyId}";
    }
}
