<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentPartyAddressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_party_id' => $this->shipment_party_id,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'city_code' => $this->city_code,
            'address' => $this->address,
            'description' => $this->description,
            'city' => $this->whenLoaded('city'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
