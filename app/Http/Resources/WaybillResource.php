<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WaybillResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
            'issued_at' => $this->issued_at?->format('Y-m-d H:i:s'),
            'sender_full_name' => $this->fullName($this->sender_first_name, $this->sender_last_name),
            'receiver_full_name' => $this->fullName($this->receiver_first_name, $this->receiver_last_name),
            'driver1_full_name' => $this->fullName($this->driver1_first_name, $this->driver1_last_name),
            'driver2_full_name' => $this->fullName($this->driver2_first_name, $this->driver2_last_name),
            'referral_driver_full_name' => $this->fullName(
                $this->referral_driver_first_name,
                $this->referral_driver_last_name,
            ),
            'sender' => ShipmentPartyResource::make($this->whenLoaded('sender')),
            'sender_address' => ShipmentPartyAddressResource::make($this->whenLoaded('senderAddress')),
            'receiver' => ShipmentPartyResource::make($this->whenLoaded('receiver')),
            'receiver_address' => ShipmentPartyAddressResource::make($this->whenLoaded('receiverAddress')),
            'first_driver' => DriverResource::make($this->whenLoaded('firstDriver')),
            'second_driver' => DriverResource::make($this->whenLoaded('secondDriver')),
            'referral_driver' => DriverResource::make($this->whenLoaded('referralDriver')),
            'fleet' => FleetResource::make($this->whenLoaded('fleet')),
            'transport_contract' => TransportContractResource::make($this->whenLoaded('transportContract')),
            'insurance' => InsuranceResource::make($this->whenLoaded('insurance')),
            'cargos' => WaybillCargoResource::collection($this->whenLoaded('cargos')),
        ];
    }

    private function fullName(?string $firstName, ?string $lastName): ?string
    {
        $fullName = trim(implode(' ', array_filter(
            [$firstName, $lastName],
            fn (?string $name): bool => filled($name),
        )));

        return $fullName === '' ? null : $fullName;
    }
}
