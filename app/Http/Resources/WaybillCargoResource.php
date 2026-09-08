<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaybillCargoResource extends JsonResource
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
            'cargo_id' => $this->cargo_id,
            'packaging_id' => $this->packaging_id,
            'title' => $this->title,
            'description' => $this->description,
            'origin_weight' => $this->numericValue($this->origin_weight),
            'value' => $this->value,
            'quantity' => $this->quantity,
            'is_traffic' => $this->is_traffic,
            'is_returned' => $this->is_returned,
            'cottage_number' => $this->cottage_number,
            'cottage_number_2' => $this->cottage_number_2,
            'driver_account_number' => $this->driver_account_number,
            'container_number' => $this->container_number,
            'container_number_2' => $this->container_number_2,
            'cargo' => $this->whenLoaded('cargo'),
            'packaging' => $this->whenLoaded('packaging'),
        ];
    }

    private function numericValue(mixed $value): int|float|null
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = (string) $value;

        if (str_contains($normalizedValue, '.')) {
            $normalizedValue = rtrim(rtrim($normalizedValue, '0'), '.');
        }

        return str_contains($normalizedValue, '.') ? (float) $normalizedValue : (int) $normalizedValue;
    }
}
