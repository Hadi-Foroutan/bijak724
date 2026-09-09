<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WaybillResource extends CompanyDynamicResource
{
    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [
            'sender' => $this->whenLoaded('sender'),
            'receiver' => $this->whenLoaded('receiver'),
            'first_driver' => $this->whenLoaded('firstDriver'),
            'second_driver' => $this->whenLoaded('secondDriver'),
            'referral_driver' => $this->whenLoaded('referralDriver'),
            'fleet' => $this->whenLoaded('fleet'),
            'transport_contract' => $this->whenLoaded('transportContract'),
            'cargos' => WaybillCargoResource::collection($this->whenLoaded('cargos')),
        ];
    }
}
