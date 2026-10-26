<?php

namespace App\Http\Resources;

use App\Models\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportDefinition */
class ReportDefinitionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // The linked fields in the user's order; `is_active: false` ones are skipped in loads and exports.
            'fields' => $this->fields->map(fn($field): array => [
                'id' => $field->id,
                'key' => $field->key,
                'label' => $field->label,
                'group' => $field->group,
                'is_active' => $field->is_active,
            ])->values(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
