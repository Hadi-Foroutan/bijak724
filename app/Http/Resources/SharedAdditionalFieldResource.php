<?php

namespace App\Http\Resources;

use App\Models\SharedAdditionalField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SharedAdditionalFieldResource extends JsonResource
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
            'number' => $this->number,
            'title' => $this->title,
            'is_visible' => $this->is_visible,
            'is_visible_in_print' => $this->is_visible_in_print,
            'field_type' => $this->field_type->value,
            'length' => $this->length,
            'layout' => $this->layout->value,
            'default_value' => $this->default_value,
            'description' => $this->description,
            'waybill_column' => SharedAdditionalField::waybillColumn($this->number),
        ];
    }
}
