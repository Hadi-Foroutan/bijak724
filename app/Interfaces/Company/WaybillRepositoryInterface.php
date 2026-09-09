<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;

interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function trackingCodeExists(int $companyId, string $trackingCode): bool;

    /** @param array<int, array<string, mixed>> $cargos */
    public function syncCargos(Waybill $waybill, int $companyId, array $cargos): void;
}
