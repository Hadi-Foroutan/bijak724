<?php

namespace App\Interfaces\Company;

use App\Models\Company\Driver;

interface DriverRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findByNationalCode(int $companyId, string $nationalCode): Driver;
}
