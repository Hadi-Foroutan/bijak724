<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShipmentPartyResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
            'addresses' => ShipmentPartyAddressResource::collection($this->whenLoaded('addresses')),
        ];
    }
}
