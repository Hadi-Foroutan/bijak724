<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A company as the insurance panel lists it: who it is and how many of its contracts the user sees. */
class InsurancePanelCompanyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'organization_code' => $this->organization_code,
            'city_code' => $this->city_code,
            'tel' => $this->tel,
            'insurances_count' => (int)$this->getAttribute('insurances_count'),
        ];
    }
}
