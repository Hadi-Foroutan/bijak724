<?php

namespace App\Services\Company\Waybill;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\WaybillCargoRepositoryInterface;
use App\Models\Company\Waybill;

class WaybillCargoService
{
    public function __construct(
        protected WaybillCargoRepositoryInterface $waybillCargoRepository,
    ) {}

    /** @param list<array<string, mixed>> $cargos */
    public function sync(Waybill $waybill, int $companyId, array $cargos): ServiceResult
    {
        $this->waybillCargoRepository->syncForWaybill($waybill, $companyId, $cargos);

        return ServiceResult::success();
    }
}
