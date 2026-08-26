<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaybillResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'company_code' => $this->company_code,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'driver1_id' => $this->driver1_id,
            'driver2_id' => $this->driver2_id,
            'fleet_id' => $this->fleet_id,
            'packaging_id' => $this->packaging_id,
            'product_owner_id' => $this->product_owner_id,
            'meta' => $this->meta,
            'sender' => $this->whenLoaded('sender'),
            'receiver' => $this->whenLoaded('receiver'),
            'first_driver' => $this->whenLoaded('firstDriver'),
            'second_driver' => $this->whenLoaded('secondDriver'),
            'fleet' => $this->whenLoaded('fleet'),
            'packaging' => $this->whenLoaded('packaging'),
            'product_owner' => $this->whenLoaded('productOwner'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
