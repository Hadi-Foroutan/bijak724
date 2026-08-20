<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'parent_type' => $this->parent_type,
            'panel_code' => $this->panel_code,
            'organization_code' => $this->organization_code,
            'name' => $this->name,
            'national_code' => $this->national_code,
            'contact_code1' => $this->contact_code1,
            'contact_code2' => $this->contact_code2,
            'contact_code3' => $this->contact_code3,
            'technical_contact_first_name' => $this->technical_contact_first_name,
            'technical_contact_last_name' => $this->technical_contact_last_name,
            'technical_contact_phone' => $this->technical_contact_phone,
            'tel' => $this->tel,
            'city_code' => $this->city_code,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'fax' => $this->fax,
            'email' => $this->email,
            'logo' => $this->logo,
            'brand' => $this->brand,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'account' => UserResource::make($this->whenLoaded('account')),
        ];
    }
}
