<?php

namespace App\Repositories\Company;

use App\Enums\ReferralNumberStatus;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Models\Company\ReferralNumber;

class ReferralNumberRepository extends CompanyModelRepository implements ReferralNumberRepositoryInterface
{
    protected string $tableKey = 'referral_numbers';

    public function active(int $companyId, ?int $ignoreId = null): ?ReferralNumber
    {
        /** @var ReferralNumber|null */
        return $this->query($companyId)
            ->where('status', ReferralNumberStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->first();
    }
}
