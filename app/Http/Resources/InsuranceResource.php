<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'insurance_company_id' => $this->insurance_company_id,
            'insurance_company' => $this->whenLoaded('insuranceCompany', fn (): array => [
                'id' => $this->insuranceCompany->id,
                'name' => $this->insuranceCompany->name,
            ]),
            'title' => $this->title,
            'contract_number' => $this->contract_number,
            'is_default' => $this->is_default,
            'status' => $this->status->value,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description,
            'representative_first_name' => $this->representative_first_name,
            'representative_last_name' => $this->representative_last_name,
            'representative_mobile' => $this->representative_mobile,
            'representative_phone' => $this->representative_phone,
            'representative_fax' => $this->representative_fax,
            'representative_email' => $this->representative_email,
            'representative_address' => $this->representative_address,
            'tariffs' => InsuranceTariffResource::collection($this->whenLoaded('tariffs')),
        ];
    }
}
