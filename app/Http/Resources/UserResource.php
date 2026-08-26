<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            //            'role' => $this->whenLoaded('roles', fn () => $this->roles->first()),
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'print_name' => $this->print_name,
            'national_code' => $this->national_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'username' => $this->username,
            'min_commission_percentage' => $this->min_commission_percentage,
            'max_commission_percentage' => $this->max_commission_percentage,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
