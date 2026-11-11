<?php

namespace App\Services\Auth;

use App\Models\User;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenService
{
    /**
     * @param  array<int, string>  $abilities
     */
    public function create(
        User $user,
        string $name = 'token',
        array $abilities = ['*'],
        ?DateTimeInterface $expiresAt = null,
    ): NewAccessToken {
        return $user->createToken($name, $abilities, $expiresAt);
    }

    /** The expiry of a token issued by a username/password login (`sanctum.login_token_expiration` minutes from now). */
    public function loginExpiresAt(): CarbonInterface
    {
        return now()->addMinutes((int) config('sanctum.login_token_expiration'));
    }

    /** Delete every token of the user whose name starts with the given prefix (e.g. `company-user:`). */
    public function revokeByNamePrefix(User $user, string $namePrefix): void
    {
        $user->tokens()->where('name', 'like', $namePrefix.'%')->delete();
    }

    public function current(User $user): ?PersonalAccessToken
    {
        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }

    public function revokeCurrent(User $user): void
    {
        $this->current($user)?->delete();
    }

    public function hasAbility(PersonalAccessToken $token, string $ability): bool
    {
        return in_array($ability, $this->abilities($token), true);
    }

    /**
     * @return array<int, string>
     */
    public function abilities(PersonalAccessToken $token): array
    {
        return $token->abilities ?? [];
    }
}
