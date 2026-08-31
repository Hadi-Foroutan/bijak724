<?php

namespace App\Http\Requests\Fleet;

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\DriverLicenseType;
use App\Models\FleetType;
use App\Models\LoadingType;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class UpdateFleetRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $fleetTable = app(CompanyDataService::class)->table($this->companyId(), 'fleets');

        return [
            'smart_card_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique($fleetTable, 'smart_card_number')->ignore((int) $this->route('fleet')),
            ],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'ownership_type' => ['sometimes', 'required', Rule::enum(FleetOwnershipType::class)],
            'plate_first_number' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'plate_second_letter' => ['sometimes', 'required', 'string', 'size:1'],
            'plate_third_number' => ['sometimes', 'required', 'string', 'regex:/^\d{3}$/'],
            'plate_fourth_number' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'manufacture_year' => ['sometimes', 'required', 'integer', 'between:1300,'.(now()->year + 1)],
            'driver_license_type_id' => ['sometimes', 'required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'owner_mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/'],
            'loading_type_id' => ['sometimes', 'required', 'integer', Rule::exists(LoadingType::class, 'id')],
            'insurance_policy_number' => ['sometimes', 'required', 'string', 'max:255'],
            'chassis_number' => ['sometimes', 'required', 'string', 'max:255'],
            'engine_number' => ['sometimes', 'required', 'string', 'max:255'],
            'vin' => ['sometimes', 'required', 'string', 'max:50'],
            'tip_code' => ['sometimes', 'required', 'integer', Rule::exists(FleetType::class, 'tip_code')],
            'document_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'document_number' => ['sometimes', 'required', 'string', 'max:255'],
            'insurance_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'technical_inspection_valid_until' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'has_violation' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
