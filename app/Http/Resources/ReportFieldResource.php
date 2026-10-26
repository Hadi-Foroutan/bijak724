<?php

namespace App\Http\Resources;

use App\Models\ReportField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportField */
class ReportFieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'group' => $this->group,
            'path' => $this->path,
            'format' => $this->format,
            'multiple' => str_contains($this->path, '*'),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'reports_count' => $this->whenCounted('reports'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
