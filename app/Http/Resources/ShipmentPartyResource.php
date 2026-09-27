<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShipmentPartyResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
            'full_name' => $this->fullName(),
            'addresses' => ShipmentPartyAddressResource::collection($this->whenLoaded('addresses')),
        ];
    }

    private function fullName(): ?string
    {
        $fullName = trim(implode(' ', array_filter(
            [$this->first_name, $this->last_name],
            fn (?string $name): bool => filled($name),
        )));

        return $fullName === '' ? null : $fullName;
    }
}
