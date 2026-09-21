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
}
