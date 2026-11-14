<?php

namespace App\Http\Resources;

use App\Models\InsuranceAgency;
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
            'insurance_company' => new InsuranceCompanyResource($this->whenLoaded('insuranceCompany')),
            'title' => $this->title,
            'contract_number' => $this->contract_number,
            'is_default' => $this->is_default,
            'status' => $this->status->value,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description,
            'insurance_agency_id' => $this->insurance_agency_id,
            // `active` = connected to its agency, `inactive` = cut off by the admin; null while the contract has no agency.
            'insurance_agency_status' => $this->insurance_agency_id === null ? null : $this->insurance_agency_status->value,
            // The agency's information is shown only while the contract is connected to it.
            'insurance_agency' => $this->whenLoaded('agency', fn(): ?array => $this->connectedAgency() === null ? null : [
                'id' => $this->connectedAgency()->id,
                'code' => $this->connectedAgency()->code,
                'city_code' => $this->connectedAgency()->city?->code,
            ]),
            // The agent's information is the agency's; a contract recorded before agencies existed keeps what it was saved with.
            // A legal agency has no first/last name: its company name is the full name.
            'representative_first_name' => $this->connectedAgency() === null ? $this->representative_first_name : $this->connectedAgency()->first_name,
            'representative_last_name' => $this->connectedAgency() === null ? $this->representative_last_name : $this->connectedAgency()->last_name,
            'representative_full_name' => $this->representativeFullName(),
            'representative_mobile' => $this->connectedAgency()?->mobile ?? $this->representative_mobile,
            'representative_phone' => $this->connectedAgency()?->phone ?? $this->representative_phone,
            'representative_fax' => $this->connectedAgency()?->fax ?? $this->representative_fax,
            'representative_email' => $this->connectedAgency()?->email ?? $this->representative_email,
            'representative_address' => $this->connectedAgency()?->address ?? $this->representative_address,
            'tariffs' => InsuranceTariffResource::collection($this->whenLoaded('tariffs')),
        ];
    }

    /** The agency while the contract is connected to it; a cut-off one is not shown, the contract's own saved data is. */
    private function connectedAgency(): ?InsuranceAgency
    {
        return $this->isConnectedToAgency() ? $this->agency : null;
    }

    private function representativeFullName(): ?string
    {
        if ($this->connectedAgency() !== null) {
            return $this->connectedAgency()->name === '' ? null : $this->connectedAgency()->name;
        }

        $fullName = trim(implode(' ', array_filter(
            [$this->representative_first_name, $this->representative_last_name],
            fn(?string $name): bool => filled($name),
        )));

        return $fullName === '' ? null : $fullName;
    }
}
