<?php

namespace App\Http\Requests\Waybill;

use App\Http\Requests\BaseRequest;
use App\Models\Packaging;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class StoreWaybillRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->waybillRules();
    }

    /** @return array<string, array<int, mixed>> */
    protected function waybillRules(): array
    {
        $companyDataService = app(CompanyDataService::class);
        $companyId = $this->companyId();

        return [
            'tracking_code' => ['nullable', 'string', 'max:255'],
            'company_code' => ['nullable', 'integer'],
            'sender_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'shipment_parties'), 'id')],
            'receiver_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'shipment_parties'), 'id')],
            'driver1_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'drivers'), 'id')],
            'driver2_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'drivers'), 'id')],
            'fleet_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'fleets'), 'id')],
            'packaging_id' => ['nullable', 'integer', Rule::exists(Packaging::class, 'id')],
            'product_owner_id' => ['nullable', 'integer', Rule::exists($companyDataService->table($companyId, 'product_owner'), 'id')],
            'meta' => ['nullable', 'array'],
        ];
    }
}
