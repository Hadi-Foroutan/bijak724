<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CanceledReferralRepositoryInterface;
use App\Models\Company\CanceledReferral;

class CanceledReferralRepository implements CanceledReferralRepositoryInterface
{
    public function __construct(protected CanceledReferral $canceledReferral) {}

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): CanceledReferral
    {
        return $this->canceledReferral
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);
    }
}
