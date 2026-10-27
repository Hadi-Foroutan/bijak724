<?php

namespace App\Http\Requests\Waybill;

use App\Enums\AdditionalFieldType;
use App\Enums\WaybillStatus;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\SharedAdditionalFieldRepositoryInterface;
use App\Models\Cargo;
use App\Models\Company\Driver;
use App\Models\Company\Fleet;
use App\Models\Company\ProductOwner;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use App\Models\Insurance;
use App\Models\Packaging;
use App\Models\SharedAdditionalField;
use App\Models\TechnicalManager;
use App\Models\TransportContract;
use Illuminate\Validation\Rule;

class StoreWaybillRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['freight_at_origin', 'is_fixed']);

        $waybillStatus = WaybillStatus::tryFrom((string) $this->input('status'));

        if ($waybillStatus !== WaybillStatus::Completed) {
            $defaults = [];

            foreach (['freight_at_origin', 'is_fixed'] as $field) {
                if ($this->exists($field) && $this->input($field) === null) {
                    $defaults[$field] = false;
                }
            }

            $this->merge($defaults);
        }

        $normalized = [];

        foreach ($this->all() as $field => $value) {
            if (
                is_string($value)
                && preg_match('/(?:amount|price|cost)$/i', (string) $field)
            ) {
                $normalized[$field] = str_replace(',', '', $value);
            }
        }

        $this->merge($normalized);

        /*$amountFields = [
            'base_freight_amount',
            'advance_freight_amount',
            'weighbridge_amount',
            'loading_amount',
            'warehousing_amount',
            'unloading_amount',
            'excess_tonnage_amount',
            'commission_amount',
            'insurance_amount',
            'insurance_tax_amount',
            'detention_amount',
            'driver_receivable_amount',
            'payable_amount',
        ];

        $normalized = [];

        foreach ($amountFields as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = str_replace(',', '', $value);
            }
        }

        $this->merge($normalized);*/
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(SharedAdditionalFieldRepositoryInterface $sharedAdditionalFieldRepository): array
    {
        $companyId = $this->companyId();
        $requiredWhenCompleted = Rule::requiredIf(
            fn (): bool => $this->hasStatus(WaybillStatus::Completed),
        );
        $requiredWhenReferral = Rule::requiredIf(
            fn (): bool => $this->hasStatus(WaybillStatus::Referral),
        );
        $requiredWhenCompletedOrReferral = Rule::requiredIf(
            fn (): bool => $this->hasStatus(WaybillStatus::Completed)
                || $this->hasStatus(WaybillStatus::Referral),
        );

        return [
            ...$this->referenceRules($requiredWhenCompletedOrReferral),
            ...$this->documentRules($requiredWhenCompleted, $requiredWhenReferral),
            ...$this->financialRules($requiredWhenCompleted),
            ...$this->cargoRules($requiredWhenCompleted, $requiredWhenCompletedOrReferral),
            ...$this->additionalFieldRules($sharedAdditionalFieldRepository, $companyId),
            // برای صدور بارنامه انتخاب مدیر فنی اجباری است؛ فعال بودن و سهمیهٔ روزانه هنگام صدور بررسی می‌شود.
            'technical_manager_id' => [
                $requiredWhenCompleted,
                'nullable',
                'integer',
                $this->existsInCompany(TechnicalManager::class),
            ],
        ];
    }

    /**
     * Additional field values are keyed by field number (1..6) and validated against that field's type.
     *
     * @return array<string, array<int, mixed>>
     */
    private function additionalFieldRules(
        SharedAdditionalFieldRepositoryInterface $sharedAdditionalFieldRepository,
        int $companyId,
    ): array {
        $fieldTypes = $sharedAdditionalFieldRepository->all($companyId)->pluck('field_type', 'number');
        $rules = [
            'additional_fields' => ['sometimes', 'nullable', 'array:'.implode(',', SharedAdditionalField::numbers())],
        ];

        foreach (SharedAdditionalField::numbers() as $number) {
            $fieldType = $fieldTypes->get($number, AdditionalFieldType::Text);
            $rules["additional_fields.{$number}"] = ['nullable', $fieldType->valueRule()];
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    private function referenceRules(mixed $requiredWhenCompletedOrReferral): array
    {
        return [
            'sender_id' => [
                $requiredWhenCompletedOrReferral,
                'nullable',
                'integer',
                $this->existsInCompany(ShipmentParty::class)->where('is_sender', true),
            ],
            'sender_address_id' => [
                $requiredWhenCompletedOrReferral,
                'nullable',
                'integer',
                $this->existsInCompany(ShipmentPartyAddress::class)
                    ->where('shipment_party_id', $this->integer('sender_id')),
            ],
            'receiver_id' => [
                $requiredWhenCompletedOrReferral,
                'nullable',
                'integer',
                $this->existsInCompany(ShipmentParty::class)->where('is_receiver', true),
            ],
            'receiver_address_id' => [
                $requiredWhenCompletedOrReferral,
                'nullable',
                'integer',
                $this->existsInCompany(ShipmentPartyAddress::class)
                    ->where('shipment_party_id', $this->integer('receiver_id')),
            ],
            'driver1_id' => [$requiredWhenCompletedOrReferral, 'nullable', 'integer', $this->existsInCompany(Driver::class)],
            'driver2_id' => ['nullable', 'integer', 'different:driver1_id', $this->existsInCompany(Driver::class)],
            'referral_driver_id' => [$requiredWhenCompletedOrReferral, 'nullable', 'integer', $this->existsInCompany(Driver::class)],
            'fleet_id' => [$requiredWhenCompletedOrReferral, 'nullable', 'integer', $this->existsInCompany(Fleet::class)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function documentRules(mixed $requiredWhenCompleted, mixed $requiredWhenReferral): array
    {
        return [
            'status' => ['required', Rule::enum(WaybillStatus::class)],
            'referral_weight' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer'],
            'loading_started_at' => [$requiredWhenReferral, 'nullable', 'date'],
            'loading_ended_at' => [$requiredWhenReferral, 'nullable', 'date', 'after_or_equal:loading_started_at'],
            'bijak_number' => [$requiredWhenCompleted, 'nullable', 'integer'],
            'serial_number' => [
                'exclude_unless:status,'.WaybillStatus::Completed->value,
                $requiredWhenCompleted,
                'nullable',
                'string',
                'max:255',
            ],
            'issued_at' => [$requiredWhenCompleted, 'nullable', 'date'],
            'liability_insurance' => [
                $requiredWhenCompleted,
                'nullable',
                'integer',
                $this->existsInCompany(Insurance::class),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function financialRules(mixed $requiredWhenComplete): array
    {
        $minimumInsuranceAmount = $this->hasStatus(WaybillStatus::Completed) ? 'min:1' : 'min:0';

        return [
            'transport_contract_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $this->existsInCompany(TransportContract::class),
            ],
            'base_freight_amount' => [$requiredWhenComplete, 'integer', $minimumInsuranceAmount],
            'advance_freight_amount' => ['nullable', 'integer', 'min:0'],
            'weighbridge_amount' => ['nullable', 'integer', 'min:0'],
            'loading_amount' => ['nullable', 'integer', 'min:0'],
            'warehousing_amount' => ['nullable', 'integer', 'min:0'],
            'unloading_amount' => ['nullable', 'integer', 'min:0'],
            'excess_tonnage_amount' => ['nullable', 'integer', 'min:0'],
            'commission_amount' => ['nullable', 'integer', 'min:0'],
            'insurance_amount' => [$requiredWhenComplete, 'nullable', 'integer', $minimumInsuranceAmount],
            'insurance_tax_amount' => [$requiredWhenComplete, 'nullable', 'integer', 'min:0'],
            'detention_amount' => ['nullable', 'integer', 'min:0'],
            'driver_receivable_amount' => ['nullable', 'integer', 'min:0'],
            'payable_amount' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                'min:0',
            ],
            'freight_at_origin' => ['nullable', 'boolean'],
            'is_fixed' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function cargoRules(mixed $requiredWhenCompleted, mixed $requiredWhenCompletedOrReferral): array
    {
        $minimumCargoCount = $this->hasStatus(WaybillStatus::Completed)
        || $this->hasStatus(WaybillStatus::Referral)
            ? 'min:1'
            : 'min:0';

        return [
            'cargos' => [$requiredWhenCompletedOrReferral, 'nullable', 'array', $minimumCargoCount, 'max:10'],
            'cargos.*.cargo_id' => [$requiredWhenCompletedOrReferral, 'nullable', 'integer', Rule::exists(Cargo::class, 'code')],
            'cargos.*.packaging_id' => [$requiredWhenCompletedOrReferral, 'nullable', 'integer', Rule::exists(Packaging::class, 'code')],
            'cargos.*.product_owner_id' => ['nullable', 'integer', $this->existsInCompany(ProductOwner::class)],
            'cargos.*.description' => ['nullable', 'string'],
            'cargos.*.title' => ['nullable', 'string', 'max:255'],
            'cargos.*.origin_weight' => ['required', 'numeric', $minimumCargoCount],
            'cargos.*.value' => [$requiredWhenCompleted, 'nullable', 'integer', $minimumCargoCount],
            'cargos.*.quantity' => [$requiredWhenCompleted, 'nullable', 'integer', 'min:1'],
            'cargos.*.is_traffic' => ['nullable', 'boolean'],
        ];
    }

    private function hasStatus(WaybillStatus $status): bool
    {
        return $this->string('status')->toString() === $status->value;
    }
}
