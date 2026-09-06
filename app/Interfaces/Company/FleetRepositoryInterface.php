<?php

namespace App\Interfaces\Company;

use App\Models\Company\Fleet;

interface FleetRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet;
}
