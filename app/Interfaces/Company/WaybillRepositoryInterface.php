<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;

interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    /** @param array<int, array<string, mixed>> $cargos */
    public function syncCargos(Waybill $waybill, int $companyId, array $cargos): void;
}
