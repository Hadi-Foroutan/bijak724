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
            'plate_two_digits' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'plate_letter' => ['sometimes', 'required', 'string', 'size:1'],
            'plate_three_digits' => ['sometimes', 'required', 'string', 'regex:/^\d{3}$/'],
            'plate_ir_number' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'manufacture_year' => ['sometimes', 'required', 'integer', 'between:1300,'.(now()->year + 1)],
            'driver_license_type_id' => ['sometimes', 'required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'owner_mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/'],
            'loading_type_id' => ['sometimes', 'required', 'integer', Rule::exists(LoadingType::class, 'id')],
            'is_loading_type_fixed' => ['sometimes', 'required', 'boolean'],
            'insurance_policy_number' => ['sometimes', 'required', 'string', 'max:255'],
            'chassis_number' => ['sometimes', 'required', 'string', 'max:255'],
            'engine_number' => ['sometimes', 'required', 'string', 'max:255'],
            'vin' => ['sometimes', 'required', 'string', 'max:50'],
            'fleet_brand_id' => ['sometimes', 'required', 'integer', Rule::exists(FleetBrand::class, 'id')],
            'fleet_type_code' => ['sometimes', 'nullable', 'integer', Rule::exists(FleetType::class, 'tip_code')],
            'document_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'document_number' => ['sometimes', 'required', 'string', 'max:255'],
            'insurance_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'technical_inspection_valid_until' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
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

            if (! $this->filled('fleet_brand_id')) {
                $validator->errors()->add('fleet_brand_id', 'برای تغییر تیپ، برند ناوگان را نیز ارسال کنید.');

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
