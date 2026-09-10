<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceTariffResource extends JsonResource
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
            'insurance_id' => $this->insurance_id,
            'cargo_group_id' => $this->cargo_group_id,
            'cargo_group' => $this->whenLoaded('cargoGroup', fn (): array => [
                'id' => $this->cargoGroup->id,
                'name' => $this->cargoGroup->name,
                'cargo_code' => $this->cargoGroup->cargo_code,
            ]),
            'cargo_value_from' => (float) $this->cargo_value_from,
            'cargo_value_to' => $this->cargo_value_to === null ? null : (float) $this->cargo_value_to,
            'fixed_premium' => $this->fixed_premium === null ? null : (float) $this->fixed_premium,
            'premium_percentage' => $this->premium_percentage === null ? null : (float) $this->premium_percentage,
            'excess_amount' => $this->excess_amount === null ? null : (float) $this->excess_amount,
            'description' => $this->description,
        ];
    }
}
