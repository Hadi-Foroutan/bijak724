<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use App\Models\Company\WaybillCargo;

/** @extends CompanyModelRepositoryInterface<WaybillCargo> */
interface WaybillCargoRepositoryInterface extends CompanyModelRepositoryInterface
{
    /** @param list<array<string, mixed>> $cargos */
    public function syncForWaybill(Waybill $waybill, int $companyId, array $cargos): void;
}
