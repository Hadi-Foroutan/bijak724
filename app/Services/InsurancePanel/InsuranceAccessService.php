<?php

namespace App\Services\InsurancePanel;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Http\Resources\InsuranceAgencyResource;
use App\Models\InsuranceAgency;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AccessTokenService;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The tokens of the insurance panel, like CompanySupportTokenService is for the company panel:
 *  - `insurance-user`: the login token of a user with the insurance role (its scope is the level of the user's agency);
 *  - `insurance-support` + `insurance-agency:{id}`: an admin's temporary token for the panel of one agency
 *    (the scope is that agency, and only the `insurance.*` routes are allowed).
 */
class InsuranceAccessService
{
    public const DEFAULT_DURATION = 1;

    private const USER_ABILITY = 'insurance-user';

    private const SUPPORT_ABILITY = 'insurance-support';

    public function __construct(protected AccessTokenService $accessTokenService)
    {
    }

    public function userTokenName(User $user): string
    {
        return "insurance-user:{$user->getKey()}:" . Str::uuid();
    }

    public function supportTokenName(InsuranceAgency $agency): string
    {
        return "insurance-support:{$agency->getKey()}:" . Str::uuid();
    }

    /** @return array<int, string> */
    public function userAbilities(): array
    {
        return [self::USER_ABILITY];
    }

    /** @return array<int, string> */
    public function supportAbilities(InsuranceAgency $agency): array
    {
        return [self::SUPPORT_ABILITY, "insurance-agency:{$agency->getKey()}"];
    }

    public function isInsuranceUser(User $user): bool
    {
        return $user->hasRole(RoleEnum::INSURANCE->value);
    }

    public function isSupportToken(?PersonalAccessToken $token): bool
    {
        return $token !== null && $this->accessTokenService->hasAbility($token, self::SUPPORT_ABILITY);
    }

    public function currentToken(User $user): ?PersonalAccessToken
    {
        return $this->accessTokenService->current($user);
    }

    public function supportRole(): ?Role
    {
        return Role::query()
            ->where('name', RoleEnum::INSURANCE->value)
            ->where('status', StatusEnum::ACTIVE->value)
            ->first();
    }

    /** @return array<int, string> the active `insurance.*` permissions of the insurance role: all an agency support token may call */
    public function supportPermissions(): array
    {
        return $this->supportRole()?->permissions()
            ->where('status', StatusEnum::ACTIVE->value)
            ->where('name', 'like', 'insurance.%')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all() ?? [];
    }

    /** Whether the user's current token may call the route: an agency support token only reaches the insurance panel. */
    public function allowsRoute(string $routeName): bool
    {
        return in_array($routeName, $this->supportPermissions(), true);
    }

    /**
     * The agency whose scope the insurance panel applies to: the user's own agency (it carries the insurer, the city and
     * the level), or, for an admin's agency support token, the agency of the token. Null when there is none (any more).
     */
    public function scope(User $user): ?InsuranceAgency
    {
        return $this->scopeFor($user, $this->currentToken($user));
    }

    /** Same as scope() for a given token (the one just issued is not yet the user's current one). */
    private function scopeFor(User $user, ?PersonalAccessToken $token): ?InsuranceAgency
    {
        $agency = $this->isSupportToken($token) ? $this->agencyOf($token) : $user->insuranceAgency;

        return $agency?->loadMissing(['insuranceCompany', 'state', 'city.state']);
    }

    /**
     * The `insurance_access` part of the authentication context, or null when the token is not an insurance panel one.
     *
     * @return array<string, mixed>|null
     */
    public function context(User $user, PersonalAccessToken $token): ?array
    {
        $isSupport = $this->isSupportToken($token);

        if (!$isSupport && !$this->isInsuranceUser($user)) {
            return null;
        }

        $agency = $this->scopeFor($user, $token);
        $city = $agency?->city;
        // A province agent and its agencies carry their state; a country agent has none (only maybe its office's city).
        $state = $agency?->state ?? $city?->state;

        return [
            'access_type' => $isSupport ? 'support' : 'insurance_user',
            'level' => $agency?->level->value,
            'level_label' => $agency?->level->label(),
            'insurance_company' => $agency === null ? null : ['id' => $agency->insuranceCompany->id, 'name' => $agency->insuranceCompany->name],
            'agency' => $agency === null ? null : InsuranceAgencyResource::make($agency)->resolve(),
            'city' => $city === null ? null : ['code' => $city->code, 'name' => $city->name],
            'state' => $state === null ? null : ['id' => $state->id, 'code' => $state->code, 'name' => $state->name],
            'issued_by' => ['id' => $user->getKey(), 'full_name' => $user->full_name],
            'permissions' => $isSupport ? $this->supportPermissions() : $user->getPermissions(),
            'expires_at' => $token->expires_at?->toIso8601String(),
        ];
    }

    private function agencyOf(PersonalAccessToken $token): ?InsuranceAgency
    {
        foreach ($this->accessTokenService->abilities($token) as $ability) {
            if (preg_match('/^insurance-agency:(\d+)$/', $ability, $matches) === 1) {
                return InsuranceAgency::query()->with('insuranceCompany')->find((int)$matches[1]);
            }
        }

        return null;
    }
}
