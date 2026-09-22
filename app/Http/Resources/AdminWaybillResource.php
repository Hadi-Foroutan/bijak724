<?php

namespace App\Http\Resources;

use App\Enums\WaybillStatus;
use App\Models\Company\Waybill as CompanyWaybill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWaybillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CompanyWaybill|null $companyWaybill */
        $companyWaybill = $this->relationLoaded('companyWaybill')
            ? $this->getRelation('companyWaybill')
            : null;
        $status = $companyWaybill === null
            ? null
            : WaybillStatus::fromIncomplete((bool) $companyWaybill->is_incomplete);

        return [
            'id' => $this->id,
            'waybill_id' => $this->waybill_id,
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn (): ?string => $this->company?->name),
            'serial_number' => $companyWaybill?->serial_number,
            'referral_number' => $companyWaybill?->referral_number,
            'bijak_tracking_code' => $companyWaybill?->bijak_tracking_code,
            'status' => $status?->value,
            'created_by' => $companyWaybill?->created_by,
            'username' => $companyWaybill?->creator?->username,
            'created_at' => $companyWaybill?->created_at ?? $this->created_at,
            'updated_at' => $companyWaybill?->updated_at ?? $this->updated_at,
        ];
    }
}
