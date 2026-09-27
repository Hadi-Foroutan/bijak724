<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyWaybillReportResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'parent_type' => $this->parent_type,
            'panel_code' => $this->panel_code,
            'organization_code' => $this->organization_code,
            'name' => $this->name,
            'national_code' => $this->national_code,
            'status' => $this->status,
            'waybills_count' => (int) $this->waybills_count,
            'last_waybill_at' => $this->last_waybill_at,
        ];
    }
}
