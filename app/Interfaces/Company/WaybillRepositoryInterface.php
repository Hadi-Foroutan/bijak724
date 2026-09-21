<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;

/** @extends CompanyModelRepositoryInterface<Waybill> */
interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function trackingCodeExists(int $companyId, string $trackingCode): bool;

    public function referralNumberExists(
        int $companyId,
        string $serialNumber,
        string $referralNumber,
        ?int $ignoreWaybillId = null,
    ): bool;

    public function bijakNumberExists(
        int $companyId,
        string $serialNumber,
        string $bijakNumber,
        ?int $ignoreWaybillId = null,
    ): bool;
}
