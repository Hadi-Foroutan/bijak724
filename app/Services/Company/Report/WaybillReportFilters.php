<?php

namespace App\Services\Company\Report;

use App\Enums\FleetOwnershipType;
use App\Enums\WaybillStatus;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The filter dialog of the report builder. These keys are applied (together with the generic advanced search keys
 * such as `eq-status` or `min-issued_at`) both when a report is loaded and when its Excel/PDF export is built, so
 * a user always gets the same waybills in the list and in the file.
 *
 * Blank values are ignored. Every filter combines with AND.
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

    /**
     * @param Builder<\App\Models\Company\Waybill> $query
     * @param array<string, mixed> $filters
     * @return Builder<\App\Models\Company\Waybill>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $filters = array_filter($filters, fn(mixed $value): bool => $value !== null && $value !== '' && $value !== []);
        $column = fn(string $name): string => $query->getModel()->qualifyColumn($name);

        $this->numberRange($query, $column('bijak_number'), $filters['bijak_number_from'] ?? null, $filters['bijak_number_to'] ?? null);
        $this->numberRange($query, $column('referral_number'), $filters['referral_number_from'] ?? null, $filters['referral_number_to'] ?? null);

        foreach (['weight' => 'referral_weight', 'quantity' => 'quantity'] as $key => $name) {
            isset($filters["{$key}_from"]) && $query->where($column($name), '>=', $filters["{$key}_from"]);
            isset($filters["{$key}_to"]) && $query->where($column($name), '<=', $filters["{$key}_to"]);
        }

        isset($filters['issued_from']) && $query->where($column('issued_at'), '>=', $filters['issued_from']);
        isset($filters['issued_to']) && $query->where($column('issued_at'), '<=', $filters['issued_to']);

        foreach ([
                     'branch_id' => 'owner_company_id', 'user_id' => 'created_by', 'status' => 'status',
                     'transport_contract_id' => 'transport_contract_id', 'insurance_id' => 'liability_insurance',
                     'sender_id' => 'sender_id', 'receiver_id' => 'receiver_id',
                 ] as $key => $name) {
            isset($filters[$key]) && $query->where($column($name), $filters[$key]);
        }

        isset($filters['freight_at_origin']) && $query->where($column('freight_at_origin'), filter_var($filters['freight_at_origin'], FILTER_VALIDATE_BOOL));

        foreach (['serial_number' => 'serial_number', 'tracking_code' => 'bijak_tracking_code', 'description' => 'description'] as $key => $name) {
            isset($filters[$key]) && $query->where($column($name), 'like', "%{$filters[$key]}%");
        }

        foreach (['sender', 'receiver'] as $side) {
            $this->party($query, $side, $filters, $column);
        }

        $this->drivers($query, $filters, $column);
        $this->fleet($query, $filters);
        $this->cargo($query, $filters);

        return $query;
    }

    /**
     * The generic advanced-search keys that express the dialog's sorting.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function sortKeys(array $filters): array
    {
        return array_filter([
            'order_field' => $filters['sort_by'] ?? null,
            'order_type' => isset($filters['sort_direction']) ? strtoupper((string)$filters['sort_direction']) : null,
        ], fn(mixed $value): bool => $value !== null && $value !== '');
    }

    /** @param array<string, mixed> $filters */
    private function party(Builder $query, string $side, array $filters, callable $column): void
    {
        foreach ([
                     "{$side}_name" => ["{$side}_full_name", 'like'],
                     "{$side}_national_identifier" => ["{$side}_national_identifier", 'like'],
                     "{$side}_postal_code" => ["{$side}_address_postal_code", 'like'],
                     "{$side}_city_code" => ["{$side}_address_city_code", '='],
                     "{$side}_address" => ["{$side}_address_address", 'like'],
                 ] as $key => [$name, $operator]) {
            if (isset($filters[$key])) {
                $query->where($column($name), $operator, $operator === 'like' ? "%{$filters[$key]}%" : $filters[$key]);
            }
        }

        if (isset($filters["{$side}_state_id"])) {
            $query->whereIn(
                $column("{$side}_address_city_code"),
                City::query()->where('state_id', $filters["{$side}_state_id"])->select('code'),
            );
        }
    }

    /**
     * National code / name of the driver, in the roles the user ticked (all three when none is ticked).
     *
     * @param array<string, mixed> $filters
     */
    private function drivers(Builder $query, array $filters, callable $column): void
    {
        $code = $filters['driver_national_code'] ?? null;
        $name = $filters['driver_name'] ?? null;

        if ($code === null && $name === null) {
            return;
        }

        $roles = array_values(array_intersect($filters['driver_roles'] ?? self::DRIVER_ROLES, self::DRIVER_ROLES)) ?: self::DRIVER_ROLES;

        $query->where(function (Builder $query) use ($roles, $code, $name, $column): void {
            foreach ($roles as $role) {
                $query->orWhere(function (Builder $query) use ($role, $code, $name, $column): void {
                    $code !== null && $query->where($column("{$role}_national_code"), 'like', "%{$code}%");
                    $name !== null && $query->where($column("{$role}_full_name"), 'like', "%{$name}%");
                });
            }
        });
    }

    /** @param array<string, mixed> $filters */
    private function fleet(Builder $query, array $filters): void
    {
        $columns = array_intersect_key($filters, array_flip([
            'smart_card_number', 'plate_first_number', 'plate_second_letter', 'plate_third_number',
            'plate_fourth_number', 'loading_type_id', 'ownership_type',
        ]));

        if ($columns === []) {
            return;
        }

        $query->whereHas('fleet', function (Builder $fleet) use ($columns): void {
            foreach ($columns as $name => $value) {
                $fleet->where($name, $name === 'smart_card_number' ? 'like' : '=', $name === 'smart_card_number' ? "%{$value}%" : $value);
            }
        });
    }

    /** All cargo filters must match the same cargo row of the waybill. @param array<string, mixed> $filters */
    private function cargo(Builder $query, array $filters): void
    {
        $columns = array_intersect_key($filters, array_flip(['cargo_id', 'packaging_id', 'product_owner_id']));
        $description = $filters['cargo_description'] ?? null;

        if ($columns === [] && $description === null) {
            return;
        }

        $query->whereHas('cargos', function (Builder $cargo) use ($columns, $description): void {
            foreach ($columns as $name => $value) {
                $cargo->where($name, $value);
            }

            $description !== null && $cargo->where(fn(Builder $match) => $match
                ->where('title', 'like', "%{$description}%")
                ->orWhere('description', 'like', "%{$description}%"));
        });
    }

    /** Bijak and referral numbers are stored as text, so they are compared as numbers. */
    private function numberRange(Builder $query, string $column, mixed $from, mixed $to): void
    {
        if ($from === null && $to === null) {
            return;
        }

        $type = DB::connection()->getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        $from !== null && $query->whereRaw("CAST({$wrapped} AS {$type}) >= ?", [(int)$from]);
        $to !== null && $query->whereRaw("CAST({$wrapped} AS {$type}) <= ?", [(int)$to]);
    }
}
