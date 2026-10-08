<?php

namespace App\Interfaces\Company;

use App\Models\Company\CanceledReferral;

interface CanceledReferralRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): CanceledReferral;
}
