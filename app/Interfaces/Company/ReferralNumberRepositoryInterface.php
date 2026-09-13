<?php

namespace App\Interfaces\Company;

use App\Models\Company\ReferralNumber;

interface ReferralNumberRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function active(int $companyId, ?int $ignoreId = null): ?ReferralNumber;
}
