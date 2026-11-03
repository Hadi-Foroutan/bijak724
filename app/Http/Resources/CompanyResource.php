<?php

namespace App\Http\Resources;

use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

class CompanyResource extends JsonResource
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
            'contact_code1' => $this->contact_code1,
            'contact_code2' => $this->contact_code2,
            'contact_code3' => $this->contact_code3,
            'technical_contact_first_name' => $this->technical_contact_first_name,
            'technical_contact_last_name' => $this->technical_contact_last_name,
            'technical_contact_phone' => $this->technical_contact_phone,
            'tel' => $this->tel,
            'city_code' => $this->city_code,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'fax' => $this->fax,
            'email' => $this->email,
            'website' => $this->website,
            'logo' => $this->logo,
            'logo_url' => $this->imageUrl($this->logo),
            'signature_url' => $this->imageUrl($this->signature),
            'brand' => $this->brand,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'account' => UserResource::make($this->whenLoaded('account')),
        ];
    }

    /** The public address of an uploaded image; a value that is not one of the uploader's files has none. */
    private function imageUrl(?string $path): ?string
    {
        try {
            return app(CompanyImageUploader::class)->url($path);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
