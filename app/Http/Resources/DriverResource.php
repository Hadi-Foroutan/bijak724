<?php

namespace App\Http\Resources;

use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'national_code' => $this->national_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'father_name' => $this->father_name,
            'license_number' => $this->license_number,
            'license_type_id' => (int) $this->license_type,
            'license_type' => $this->whenLoaded('licenseType'),
            'license_expiry_date' => $this->license_expiry_date,
            'phone_number_1' => $this->phone_number_1,
            'phone_number_2' => $this->phone_number_2,
            'phone_number_3' => $this->phone_number_3,
            'profile_image_url' => app(CompanyImageUploader::class)->url($this->profile_image_path),
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
