<?php

namespace App\Services\Company\TransportContract;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\Company\CompanySupportTokenService;

class TransportContractAccessService
{
    public function __construct(
        protected CompanySupportTokenService $companySupportTokenService,
    ) {}

    public function canViewAll(User $user): bool
    {
        return $user->hasRole(RoleEnum::COMPANY_MANAGER->value)
            || $this->companySupportTokenService->isSupportToken($user);
    }
}
