<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use App\Models\Company\WaybillCargo;
use Illuminate\Database\Eloquent\Builder;

interface WaybillCargoRepositoryInterface
{
    /** @return Builder<WaybillCargo> */
    public function query(int $companyId): Builder;

    /** @param list<array<string, mixed>> $cargos */
    public function syncForWaybill(Waybill $waybill, int $companyId, array $cargos): void;
}
