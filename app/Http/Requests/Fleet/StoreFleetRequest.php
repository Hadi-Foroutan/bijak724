<?php

namespace App\Http\Requests\Fleet;

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use Illuminate\Validation\Rule;

class StoreFleetRequest extends BaseRequest
{
    /** @var list<string> */
    private const DATE_FIELDS = [
        'document_date',
        'insurance_date',
        'technical_inspection_valid_until',
    ];

    /** @return array<string, array<int, mixed>> */
    public function rules(FleetRepositoryInterface $fleetRepository): array
    {
        return [
            'smart_card_number' => [
                'nullable',
                'string',
                'max:50',
                $fleetRepository->uniqueSmartCardNumberRule($this->companyId()),
            ],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
            'ownership_type' => ['nullable', Rule::enum(FleetOwnershipType::class)],
            'plate_first_number' => ['required', 'string', 'regex:/^\d{2}$/'],
            'plate_second_letter' => ['required', 'string', 'min:1', 'max:1'],
            'plate_third_number' => ['required', 'string', 'regex:/^\d{3}$/'],
            'plate_fourth_number' => ['required', 'string', 'regex:/^\d{2}$/'],
            'manufacture_year' => ['nullable', 'integer', 'between:1300,'.(now()->year + 1)],
            'driver_license_type_id' => ['nullable', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'owner_mobile' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'loading_type_id' => ['nullable', 'integer', Rule::exists(LoadingType::class, 'id')],
            'insurance_policy_number' => ['nullable', 'string', 'max:255'],
            'chassis_number' => ['nullable', 'string', 'max:255'],
            'engine_number' => ['nullable', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'max:50'],
            'system_id' => ['nullable', 'integer', Rule::exists(FleetBrand::class, 'id')],
            'tip_code' => ['nullable', 'integer', Rule::exists(FleetType::class, 'tip_code')],
            'document_date' => ['nullable', Rule::date()->format('Y-m-d')],
            'document_number' => ['nullable', 'string', 'max:255'],
            'insurance_date' => ['nullable', Rule::date()->format('Y-m-d')],
            'technical_inspection_valid_until' => ['nullable', Rule::date()->format('Y-m-d')],
            'has_violation' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ($this->except([
            'plate_first_number',
            'plate_second_letter',
            'plate_third_number',
            'plate_fourth_number',
        ]) as $field => $value) {
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
