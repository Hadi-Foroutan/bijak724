<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;

/** @extends CompanyModelRepository<Waybill> */
class WaybillRepository extends CompanyModelRepository implements WaybillRepositoryInterface
{
    protected string $modelClass = Waybill::class;

    public function trackingCodeExists(int $companyId, string $trackingCode): bool
    {
        return $this->sharedQuery($companyId)
            ->where('bijak_tracking_code', $trackingCode)
            ->exists();
    }

    public function referralNumberExists(
        int $companyId,
        string $serialNumber,
        string $referralNumber,
        ?int $ignoreWaybillId = null,
    ): bool {
        return $this->numberExists(
            $companyId,
            $serialNumber,
            'referral_number',
            $referralNumber,
            $ignoreWaybillId,
        );
    }

    public function bijakNumberExists(
        int $companyId,
        string $serialNumber,
        string $bijakNumber,
        ?int $ignoreWaybillId = null,
    ): bool {
        return $this->numberExists(
            $companyId,
            $serialNumber,
            'bijak_number',
            $bijakNumber,
            $ignoreWaybillId,
        );
    }

    public function update(int $companyId, int $id, array $data): Waybill
    {
        unset($data['owner_company_id']);

        /** @var Waybill $waybill */
        $waybill = $this->findOrFail($companyId, $id);
        $waybill->fill($data);

        if ($waybill->isDirty()) {
            $waybill->save();
        }

        /** @var Waybill $waybill */
        $waybill = $this->loadRelations($waybill->refresh());

        return $waybill;
    }

    private function numberExists(
        int $companyId,
        string $serialNumber,
        string $numberColumn,
        string $number,
        ?int $ignoreWaybillId,
    ): bool {
        return $this->query($companyId)
            ->where('serial_number', $serialNumber)
            ->where($numberColumn, $number)
            ->when($ignoreWaybillId !== null, fn ($query) => $query->whereKeyNot($ignoreWaybillId))
            ->exists();
    }
}
