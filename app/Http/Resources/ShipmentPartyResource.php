<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentPartyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'national_identifier' => $this->national_identifier,
            'type' => $this->type,
            'status' => $this->status,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'mobile' => $this->mobile,
            'landline' => $this->landline,
            'intermediary_code' => $this->intermediary_code,
            'transportation_code' => $this->transportation_code,
            'email' => $this->email,
            'description' => $this->description,
            'addresses' => ShipmentPartyAddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
