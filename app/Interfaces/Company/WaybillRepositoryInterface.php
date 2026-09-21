<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;

/** @extends CompanyModelRepositoryInterface<Waybill> */
interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function trackingCodeExists(int $companyId, string $trackingCode): bool;
}
