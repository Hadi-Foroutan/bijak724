<?php

namespace App\Services\Company\Waybill;

use App\Interfaces\Company\WaybillRepositoryInterface;

class WaybillTrackingCodeGenerator
{
    public function __construct(protected WaybillRepositoryInterface $waybillRepository) {}

    public function generate(int $companyId): string
    {
        do {
            $trackingCode = (string) random_int(10_000_000, 99_999_999);
        } while ($this->waybillRepository->trackingCodeExists($companyId, $trackingCode));

        return $trackingCode;
    }
}
