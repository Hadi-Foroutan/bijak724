<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceAgencyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'code' => $this->code,
            'insurance_company_id' => $this->insurance_company_id,
            'insurance_company' => new InsuranceCompanyResource($this->whenLoaded('insuranceCompany')),
            'state_code' => $this->state_code,
            'state' => $this->whenLoaded('state', fn(): ?array => $this->state === null ? null : [
                'id' => $this->state->id,
                'code' => $this->state->code,
                'name' => $this->state->name,
            ]),
            'city_id' => $this->city_id,
            'city_code' => $this->whenLoaded('city', fn(): ?int => $this->city?->code),
            'city' => $this->whenLoaded('city', fn(): ?array => $this->city === null ? null : [
                'id' => $this->city->id,
                'code' => $this->city->code,
                'name' => $this->city->name,
            ]),
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'company_name' => $this->company_name,
            'company_national_id' => $this->company_national_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'national_code' => $this->national_code,
            'full_name' => $this->full_name,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
