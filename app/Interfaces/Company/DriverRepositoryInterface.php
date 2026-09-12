<?php

namespace App\Interfaces\Company;

use App\Models\Company\Driver;
use Illuminate\Validation\Rules\Unique;

interface DriverRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findByNationalCode(int $companyId, string $nationalCode): Driver;

    public function uniqueNationalCodeRule(int $companyId, ?int $ignoreDriverId = null): Unique;
}
