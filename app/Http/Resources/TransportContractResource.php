<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportContractResource extends JsonResource
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
            'company_id' => $this->company_id,
            'title' => $this->title,
            'contract_number' => $this->contract_number,
            'contract_date' => $this->contract_date?->format('Y-m-d'),
            'customer_name' => $this->customer_name,
            'status' => $this->status->value,
            'is_default' => $this->is_default,
            'default_owned' => $this->default_owned,
            'default_rental' => $this->default_rental,
            'default_free' => $this->default_free,
            'default_unknown' => $this->default_unknown,
            'description' => $this->description,
            'items' => TransportContractItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
