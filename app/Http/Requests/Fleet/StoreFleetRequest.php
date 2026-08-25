<?php

namespace App\Http\Requests\Fleet;

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFleetRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $fleetTable = app(CompanyDataService::class)->table($this->companyId(), 'fleets');

        return [
            'smart_card_number' => ['required', 'string', 'max:50', Rule::unique($fleetTable, 'smart_card_number')],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'ownership_type' => ['required', Rule::enum(FleetOwnershipType::class)],
            'plate_two_digits' => ['required', 'string', 'regex:/^\d{2}$/'],
            'plate_letter' => ['required', 'string', 'size:1'],
            'plate_three_digits' => ['required', 'string', 'regex:/^\d{3}$/'],
            'plate_ir_number' => ['required', 'string', 'regex:/^\d{2}$/'],
            'manufacture_year' => ['required', 'integer', 'between:1300,'.(now()->year + 1)],
            'driver_license_type_id' => ['required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'owner_mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'loading_type_id' => ['required', 'integer', Rule::exists(LoadingType::class, 'id')],
            'is_loading_type_fixed' => ['required', 'boolean'],
            'insurance_policy_number' => ['required', 'string', 'max:255'],
            'chassis_number' => ['required', 'string', 'max:255'],
            'engine_number' => ['required', 'string', 'max:255'],
            'vin' => ['required', 'string', 'max:50'],
            'fleet_brand_id' => ['required', 'integer', Rule::exists(FleetBrand::class, 'id')],
            'fleet_type_code' => ['nullable', 'integer', Rule::exists(FleetType::class, 'tip_code')],
            'document_date' => ['required', Rule::date()->format('Y-m-d')],
            'document_number' => ['required', 'string', 'max:255'],
            'insurance_date' => ['required', Rule::date()->format('Y-m-d')],
            'technical_inspection_valid_until' => ['required', Rule::date()->format('Y-m-d')],
            'has_violation' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['fleet_brand_id', 'fleet_type_code'])
                || ! $this->filled('fleet_type_code')) {
                return;
            }

            $brandCode = FleetBrand::query()
                ->whereKey($this->integer('fleet_brand_id'))
                ->value('brand_code');

            $fleetTypeBelongsToBrand = FleetType::query()
                ->whereKey($this->integer('fleet_type_code'))
                ->where('brand_code', $brandCode)
                ->exists();

            if (! $fleetTypeBelongsToBrand) {
                $validator->errors()->add('fleet_type_code', 'تیپ انتخاب‌شده متعلق به برند ناوگان نیست.');
            }
        }];
    }
}
