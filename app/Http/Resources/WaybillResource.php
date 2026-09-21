<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WaybillResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
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
}
