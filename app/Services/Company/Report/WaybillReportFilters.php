<?php

namespace App\Services\Company\Report;

use App\Enums\FleetOwnershipType;
use App\Enums\WaybillStatus;
use Illuminate\Validation\Rule;

/**
 * Validation rules of the report builder's filter dialog. The keys are applied (together with the generic advanced
 * search keys such as `eq-status` or `min-issued_at`) both when a report is loaded and when its Excel/PDF export is
 * built, so a user always gets the same waybills in the list and in the file. The query side lives in
 * App\Repositories\Company\WaybillReportFilterApplier.
 */
class WaybillReportFilters
{
    /** Waybill columns that hold the snapshot of each driver role. */
    private const DRIVER_ROLES = ['driver1', 'driver2', 'referral_driver'];

    /** @return array<string, array<int, mixed>> validation rules; `$prefix` is `filters.` for the export request */
    public static function rules(string $prefix = ''): array
    {
        $rules = [
            'bijak_number_from' => ['nullable', 'integer', 'min:0'],
            'bijak_number_to' => ['nullable', 'integer', 'min:0'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'tracking_code' => ['nullable', 'string', 'max:50'],
            'issued_from' => ['nullable', 'date'],
            'issued_to' => ['nullable', 'date'],
            'referral_number_from' => ['nullable', 'integer', 'min:0'],
            'referral_number_to' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transport_contract_id' => ['nullable', 'integer'],
            'insurance_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(WaybillStatus::class)],
            'driver_roles' => ['nullable', 'array'],
            'driver_roles.*' => ['string', Rule::in(self::DRIVER_ROLES)],
            'driver_national_code' => ['nullable', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'smart_card_number' => ['nullable', 'string', 'max:50'],
            'plate_first_number' => ['nullable', 'string', 'max:2'],
            'plate_second_letter' => ['nullable', 'string', 'max:3'],
            'plate_third_number' => ['nullable', 'string', 'max:3'],
            'plate_fourth_number' => ['nullable', 'string', 'max:2'],
            'loading_type_id' => ['nullable', 'integer'],
            'ownership_type' => ['nullable', Rule::enum(FleetOwnershipType::class)],
            'cargo_id' => ['nullable', 'integer'],
            'cargo_description' => ['nullable', 'string', 'max:255'],
            'packaging_id' => ['nullable', 'integer'],
            'product_owner_id' => ['nullable', 'integer'],
            'freight_at_origin' => ['nullable', 'boolean'],
            'weight_from' => ['nullable', 'numeric', 'min:0'],
            'weight_to' => ['nullable', 'numeric', 'min:0'],
            'quantity_from' => ['nullable', 'integer', 'min:0'],
            'quantity_to' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc', 'ASC', 'DESC'])],
        ];

        foreach (['sender', 'receiver'] as $side) {
            $rules += [
                "{$side}_id" => ['nullable', 'integer'],
                "{$side}_name" => ['nullable', 'string', 'max:255'],
                "{$side}_national_identifier" => ['nullable', 'string', 'max:20'],
                "{$side}_postal_code" => ['nullable', 'string', 'max:20'],
                "{$side}_state_id" => ['nullable', 'integer'],
                "{$side}_city_code" => ['nullable', 'integer'],
                "{$side}_address" => ['nullable', 'string', 'max:255'],
            ];
        }

        return $prefix === '' ? $rules : array_combine(
            array_map(fn(string $key): string => $prefix . $key, array_keys($rules)),
            array_values($rules),
        );
    }
}
