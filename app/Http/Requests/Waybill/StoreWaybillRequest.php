<?php

namespace App\Http\Requests\Waybill;

use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Cargo;
use App\Models\Packaging;
use App\Models\TransportContract;
use Illuminate\Validation\Rule;

class StoreWaybillRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['is_incomplete', 'freight_at_origin', 'is_fixed']);

        if (! $this->boolean('is_incomplete', true)) {
            return;
        }

        $defaults = [];

        foreach (['freight_at_origin', 'is_fixed'] as $field) {
            if ($this->exists($field) && $this->input($field) === null) {
                $defaults[$field] = false;
            }
        }

        $this->merge($defaults);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(WaybillRepositoryInterface $waybillRepository): array
    {
        return $this->waybillRules($waybillRepository);
    }

    /** @return array<string, array<int, mixed>> */
    protected function waybillRules(WaybillRepositoryInterface $waybillRepository): array
    {
        $companyId = $this->companyId();
        $requiredWhenComplete = Rule::requiredIf(fn (): bool => ! $this->boolean('is_incomplete', true));

        return [
            ...$this->referenceRules($waybillRepository, $companyId, $requiredWhenComplete),
            ...$this->documentRules($waybillRepository, $companyId, $requiredWhenComplete),
            ...$this->financialRules($companyId, $requiredWhenComplete),
            ...$this->cargoRules($waybillRepository, $companyId, $requiredWhenComplete),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function referenceRules(
        WaybillRepositoryInterface $waybillRepository,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        return [
            'sender_id' => [$requiredWhenComplete, 'nullable', 'integer', $waybillRepository->senderExistsRule($companyId)],
            'sender_address_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $waybillRepository->shipmentPartyAddressExistsRule(
                    $companyId,
                    $this->integer('sender_id'),
                ),
            ],
            'receiver_id' => [$requiredWhenComplete, 'nullable', 'integer', $waybillRepository->receiverExistsRule($companyId)],
            'receiver_address_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $waybillRepository->shipmentPartyAddressExistsRule(
                    $companyId,
                    $this->integer('receiver_id'),
                ),
            ],
            'driver1_id' => [$requiredWhenComplete, 'nullable', 'integer', $waybillRepository->driverExistsRule($companyId)],
            'driver2_id' => ['nullable', 'integer', 'different:driver1_id', $waybillRepository->driverExistsRule($companyId)],
            'referral_driver_id' => [$requiredWhenComplete, 'nullable', 'integer', $waybillRepository->driverExistsRule($companyId)],
            'fleet_id' => [$requiredWhenComplete, 'nullable', 'integer', $waybillRepository->fleetExistsRule($companyId)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function documentRules(
        WaybillRepositoryInterface $waybillRepository,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        return [
            'is_incomplete' => ['required', 'boolean'],
            'referral_weight' => [$requiredWhenComplete, 'nullable', 'numeric', 'min:0'],
            'quantity' => [$requiredWhenComplete, 'nullable', 'integer', 'min:1'],
            'loading_started_at' => [$requiredWhenComplete, 'nullable', 'date'],
            'loading_ended_at' => [$requiredWhenComplete, 'nullable', 'date', 'after_or_equal:loading_started_at'],
            'referral_number' => ['nullable', 'string', 'max:255'],
            'bijak_number' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'issued_at' => [$requiredWhenComplete, 'nullable', 'date'],
            'liability_insurance' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $waybillRepository->insuranceExistsRule($companyId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function financialRules(int $companyId, mixed $requiredWhenComplete): array
    {
        return [
            'transport_contract_id' => [$requiredWhenComplete, 'nullable', 'integer', Rule::exists(TransportContract::class, 'id')->where('company_id', $companyId)],
            'base_freight_amount' => [$requiredWhenComplete, 'nullable', 'integer', 'min:0'],
            'advance_freight_amount' => ['nullable', 'integer', 'min:0'],
            'weighbridge_amount' => ['nullable', 'integer', 'min:0'],
            'loading_amount' => ['nullable', 'integer', 'min:0'],
            'warehousing_amount' => ['nullable', 'integer', 'min:0'],
            'commission_amount' => ['nullable', 'integer', 'min:0'],
            'insurance_amount' => ['nullable', 'integer', 'min:0'],
            'insurance_tax_amount' => ['nullable', 'integer', 'min:0'],
            'detention_amount' => ['nullable', 'integer', 'min:0'],
            'driver_receivable_amount' => ['nullable', 'integer', 'min:0'],
            'payable_amount' => [
                Rule::requiredIf(fn (): bool => ! $this->boolean('is_incomplete', true) && $this->boolean('is_fixed')),
                'nullable',
                'integer',
                'min:0',
            ],
            'freight_at_origin' => [$requiredWhenComplete, 'nullable', 'boolean'],
            'is_fixed' => [$requiredWhenComplete, 'nullable', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function cargoRules(WaybillRepositoryInterface $waybillRepository, int $companyId, mixed $requiredWhenComplete): array
    {
        $minimumCargoCount = $this->boolean('is_incomplete', true) ? 'min:0' : 'min:1';

        return [
            'cargos' => [$requiredWhenComplete, 'nullable', 'array', $minimumCargoCount, 'max:10'],
            'cargos.*.cargo_id' => [$requiredWhenComplete, 'nullable', 'integer', Rule::exists(Cargo::class, 'code')],
            'cargos.*.packaging_id' => [$requiredWhenComplete, 'nullable', 'integer', Rule::exists(Packaging::class, 'code')],
            'cargos.*.product_owner_id' => ['nullable', 'integer', $waybillRepository->productOwnerExistsRule($companyId)],
            'cargos.*.description' => ['nullable', 'string'],
            'cargos.*.title' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'cargos.*.origin_weight' => [$requiredWhenComplete, 'nullable', 'numeric', 'min:0'],
            'cargos.*.value' => [$requiredWhenComplete, 'nullable', 'integer', 'min:0'],
            'cargos.*.quantity' => [$requiredWhenComplete, 'nullable', 'integer', 'min:1'],
            'cargos.*.is_traffic' => [$requiredWhenComplete, 'nullable', 'boolean'],
            'cargos.*.is_returned' => [$requiredWhenComplete, 'nullable', 'boolean'],
            'cargos.*.cottage_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.cottage_number_2' => ['nullable', 'string', 'max:255'],
            'cargos.*.driver_account_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.container_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.container_number_2' => ['nullable', 'string', 'max:255'],
        ];
    }
}
