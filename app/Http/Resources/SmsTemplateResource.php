<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'party' => $this->party->value,
            'party_label' => $this->party->label(),
            'body' => $this->body,
            'updated_at' => $this->updated_at,
        ];
    }
}
