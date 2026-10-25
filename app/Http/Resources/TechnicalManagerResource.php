<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TechnicalManager */
class TechnicalManagerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $issuedToday = $this->getAttribute('issued_today');

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'full_name' => $this->full_name,
            'national_code' => $this->national_code,
            'mobile' => $this->mobile,
            'status' => $this->status->value,
            'daily_limit' => $this->daily_limit,
            'issued_today' => $issuedToday,
            'remaining_today' => $issuedToday === null ? null : $this->remainingToday(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
