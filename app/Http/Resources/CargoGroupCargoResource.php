<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CargoGroupCargoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->cargo?->id,
            'name' => $this->cargo?->name,
            'code' => $this->cargo?->code,
            'description' => $this->cargo?->description,
        ];
    }
}
