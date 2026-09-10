<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CargoGroupResource extends JsonResource
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
            'name' => $this->name,
            'cargo_code' => $this->cargo_code,
            'status' => $this->status->value,
            'cargos' => CargoGroupCargoResource::collection($this->whenLoaded('cargoAssignments')),
        ];
    }
}
