<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShipmentPartyAddressResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
            'city' => $this->whenLoaded('city'),
        ];
    }
}
