<?php

namespace App\Http\Resources;

use App\Enums\FleetOwnershipType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FleetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'smart_card_number' => $this->smart_card_number,
            'status' => $this->status,
            'ownership_type' => $this->ownership_type,
//            'ownership_type_label' => FleetOwnershipType::tryFrom($this->ownership_type)?->label(),
            'plate_two_digits' => $this->plate_two_digits,
            'plate_letter' => $this->plate_letter,
            'plate_three_digits' => $this->plate_three_digits,
            'plate_ir_number' => $this->plate_ir_number,
            'manufacture_year' => $this->manufacture_year,
            'driver_license_type_id' => $this->driver_license_type_id,
            'owner_mobile' => $this->owner_mobile,
            'loading_type_id' => $this->loading_type_id,
            'is_loading_type_fixed' => (bool) $this->is_loading_type_fixed,
            'insurance_policy_number' => $this->insurance_policy_number,
            'chassis_number' => $this->chassis_number,
            'engine_number' => $this->engine_number,
            'vin' => $this->vin,
            'fleet_brand_id' => $this->fleet_brand_id,
            'fleet_type_code' => $this->fleet_type_code,
            'document_date' => $this->document_date,
            'document_number' => $this->document_number,
            'insurance_date' => $this->insurance_date,
            'technical_inspection_valid_until' => $this->technical_inspection_valid_until,
            'has_violation' => (bool) $this->has_violation,
            'driver_license_type' => $this->whenLoaded(
                'driverLicenseType',
                fn (): ?array => $this->driverLicenseType === null ? null : [
                    'id' => $this->driverLicenseType->id,
                    'name' => $this->driverLicenseType->name,
                    'code' => $this->driverLicenseType->code,
                ],
            ),
            'loading_type' => $this->whenLoaded(
                'loadingType',
                fn (): ?array => $this->loadingType === null ? null : [
                    'id' => $this->loadingType->id,
                    'name' => $this->loadingType->name,
                    'code' => $this->loadingType->code,
                ],
            ),
            'fleet_brand' => $this->whenLoaded(
                'fleetBrand',
                fn (): ?array => $this->fleetBrand === null ? null : [
                    'id' => $this->fleetBrand->id,
                    'name' => $this->fleetBrand->name,
                    'brand_code' => $this->fleetBrand->brand_code,
                ],
            ),
            'fleet_type' => $this->whenLoaded(
                'fleetType',
                fn (): ?array => $this->fleetType === null ? null : [
                    'tip_code' => $this->fleetType->tip_code,
                    'name' => $this->fleetType->name,
                    'brand_code' => $this->fleetType->brand_code,
                ],
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
