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

class UpdateFleetRequest extends BaseRequest
{
    /** @var list<string> */
    private const DATE_FIELDS = [
        'document_date',
        'insurance_date',
        'technical_inspection_valid_until',
    ];

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $fleetTable = app(CompanyDataService::class)->table($this->companyId(), 'fleets');

        return [
            'smart_card_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique($fleetTable, 'smart_card_number')->ignore((int) $this->route('fleet')),
            ],
            'status' => ['sometimes', 'nullable', Rule::enum(StatusEnum::class)],
            'ownership_type' => ['sometimes', 'nullable', Rule::enum(FleetOwnershipType::class)],
            'plate_first_number' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'plate_second_letter' => ['sometimes', 'required', 'string', 'min:1', 'max:1'],
            'plate_third_number' => ['sometimes', 'required', 'string', 'regex:/^\d{3}$/'],
            'plate_fourth_number' => ['sometimes', 'required', 'string', 'regex:/^\d{2}$/'],
            'manufacture_year' => ['sometimes', 'nullable', 'integer', 'between:1300,'.(now()->year + 1)],
            'driver_license_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'owner_mobile' => ['sometimes', 'nullable', 'string', 'regex:/^09\d{9}$/'],
            'loading_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists(LoadingType::class, 'id')],
            'insurance_policy_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'chassis_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'engine_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vin' => ['sometimes', 'nullable', 'string', 'max:50'],
            'system_id' => ['sometimes', 'nullable', 'integer', Rule::exists(FleetBrand::class, 'id')],
            'tip_code' => ['sometimes', 'nullable', 'integer', Rule::exists(FleetType::class, 'tip_code')],
            'document_date' => ['sometimes', 'nullable', Rule::date()->format('Y-m-d')],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'insurance_date' => ['sometimes', 'nullable', Rule::date()->format('Y-m-d')],
            'technical_inspection_valid_until' => ['sometimes', 'nullable', Rule::date()->format('Y-m-d')],
            'has_violation' => ['sometimes', 'nullable', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ($this->all() as $field => $value) {
            if ($this->isEmptyFormValue($value)) {
                $normalized[$field] = null;
            }
        }

        foreach (self::DATE_FIELDS as $dateField) {
            if (array_key_exists($dateField, $this->all())
                && ! $this->isFormattedDate($this->input($dateField))) {
                $normalized[$dateField] = null;
            }
        }

        $this->merge($normalized);
    }

    private function isFormattedDate(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    private function isEmptyFormValue(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['', 'null', 'undefined', 'invalid date'], true)
            || $value === 'nan'
            || str_contains($value, 'nan-')
            || str_contains($value, '-nan');
    }
}
