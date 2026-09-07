<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportContractItemResource extends JsonResource
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
            'name' => $this->name->value,
            'name_label' => $this->name->label(),
            'is_owned' => $this->is_owned,
            'is_rental' => $this->is_rental,
            'is_free' => $this->is_free,
            'is_unknown' => $this->is_unknown,
            'charge_recipient' => $this->charge_recipient,
            'primary_value' => $this->primary_value,
            'primary_value_label' => $this->name->primaryValueLabel(),
            'secondary_value' => $this->secondary_value,
            'secondary_value_label' => $this->name->secondaryValueLabel(),
            'editable_fields' => $this->name->editableFields(),
        ];
    }
}
