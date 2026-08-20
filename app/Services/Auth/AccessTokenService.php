<?php

namespace App\Services\Auth;

use App\Models\User;
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
