<?php

namespace App\Http\Requests\Waybill;

use App\Enums\WaybillStatus;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Models\Cargo;
use App\Models\Packaging;
use Illuminate\Validation\Rule;

class StoreWaybillRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['freight_at_origin', 'is_fixed']);
        $waybillStatus = WaybillStatus::tryFrom((string) $this->input('status'));

        if ($waybillStatus === WaybillStatus::Completed) {
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
    public function rules(
        ShipmentPartyRepositoryInterface $shipmentPartyRepository,
        ShipmentPartyAddressRepositoryInterface $shipmentPartyAddressRepository,
        DriverRepositoryInterface $driverRepository,
        FleetRepositoryInterface $fleetRepository,
        InsuranceRepositoryInterface $insuranceRepository,
        TransportContractRepositoryInterface $transportContractRepository,
        ProductOwnerRepositoryInterface $productOwnerRepository,
    ): array {
        $companyId = $this->companyId();
        $requiredWhenCompleted = Rule::requiredIf(
            fn (): bool => $this->hasStatus(WaybillStatus::Completed),
        );
        $requiredWhenReferral = Rule::requiredIf(
            fn (): bool => $this->hasStatus(WaybillStatus::Referral),
        );

        return [
            ...$this->referenceRules(
                $shipmentPartyRepository,
                $shipmentPartyAddressRepository,
                $driverRepository,
                $fleetRepository,
                $companyId,
                $requiredWhenCompleted,
            ),
            ...$this->documentRules(
                $insuranceRepository,
                $companyId,
                $requiredWhenCompleted,
                $requiredWhenReferral,
            ),
            ...$this->financialRules($transportContractRepository, $companyId, $requiredWhenCompleted),
            ...$this->cargoRules($productOwnerRepository, $companyId, $requiredWhenCompleted),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function referenceRules(
        ShipmentPartyRepositoryInterface $shipmentPartyRepository,
        ShipmentPartyAddressRepositoryInterface $shipmentPartyAddressRepository,
        DriverRepositoryInterface $driverRepository,
        FleetRepositoryInterface $fleetRepository,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        return [
            'sender_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $shipmentPartyRepository->existsRule($companyId)->where('is_sender', true),
            ],
            'sender_address_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $shipmentPartyAddressRepository->existsRule($companyId)
                    ->where('shipment_party_id', $this->integer('sender_id')),
            ],
            'receiver_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $shipmentPartyRepository->existsRule($companyId)->where('is_receiver', true),
            ],
            'receiver_address_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $shipmentPartyAddressRepository->existsRule($companyId)
                    ->where('shipment_party_id', $this->integer('receiver_id')),
            ],
            'driver1_id' => [$requiredWhenComplete, 'nullable', 'integer', $driverRepository->existsRule($companyId)],
            'driver2_id' => ['nullable', 'integer', 'different:driver1_id', $driverRepository->existsRule($companyId)],
            'referral_driver_id' => [$requiredWhenComplete, 'nullable', 'integer', $driverRepository->existsRule($companyId)],
            'fleet_id' => [$requiredWhenComplete, 'nullable', 'integer', $fleetRepository->existsRule($companyId)],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function documentRules(
        InsuranceRepositoryInterface $insuranceRepository,
        int $companyId,
        mixed $requiredWhenCompleted,
        mixed $requiredWhenReferral,
    ): array {
        return [
            'status' => ['required', Rule::enum(WaybillStatus::class)],
            'referral_weight' => [$requiredWhenReferral, 'nullable', 'numeric', 'min:0'],
            'quantity' => [$requiredWhenReferral, 'nullable', 'integer', 'min:1'],
            'loading_started_at' => [$requiredWhenReferral, 'nullable', 'date'],
            'loading_ended_at' => [$requiredWhenReferral, 'nullable', 'date', 'after_or_equal:loading_started_at'],
            'referral_number' => [$requiredWhenReferral, 'nullable', 'string', 'max:255'],
            'bijak_number' => [$requiredWhenCompleted, 'nullable', 'integer'],
            'serial_number' => [$requiredWhenCompleted, 'nullable', 'string', 'max:255'],
            'issued_at' => [$requiredWhenCompleted, 'nullable', 'date'],
            'liability_insurance' => [
                $requiredWhenCompleted,
                'nullable',
                'integer',
                $insuranceRepository->existsRule($companyId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function financialRules(
        TransportContractRepositoryInterface $transportContractRepository,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        return [
            'transport_contract_id' => [
                $requiredWhenComplete,
                'nullable',
                'integer',
                $transportContractRepository->existsRule($companyId),
            ],
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
                Rule::requiredIf(fn (): bool => $this->hasStatus(WaybillStatus::Completed) && $this->boolean('is_fixed')),
                'nullable',
                'integer',
                'min:0',
            ],
            'freight_at_origin' => ['nullable', 'boolean'],
            'is_fixed' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function cargoRules(
        ProductOwnerRepositoryInterface $productOwnerRepository,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        $minimumCargoCount = $this->hasStatus(WaybillStatus::Completed) ? 'min:1' : 'min:0';

        return [
            'cargos' => [$requiredWhenComplete, 'nullable', 'array', $minimumCargoCount, 'max:10'],
            'cargos.*.cargo_id' => [$requiredWhenComplete, 'nullable', 'integer', Rule::exists(Cargo::class, 'code')],
            'cargos.*.packaging_id' => [$requiredWhenComplete, 'nullable', 'integer', Rule::exists(Packaging::class, 'code')],
            'cargos.*.product_owner_id' => ['nullable', 'integer', $productOwnerRepository->existsRule($companyId)],
            'cargos.*.description' => ['nullable', 'string'],
            'cargos.*.title' => ['nullable', 'string', 'max:255'],
            'cargos.*.origin_weight' => [$requiredWhenComplete, 'nullable', 'numeric', 'min:0'],
            'cargos.*.value' => [$requiredWhenComplete, 'nullable', 'integer', 'min:0'],
            'cargos.*.quantity' => [$requiredWhenComplete, 'nullable', 'integer', 'min:1'],
            'cargos.*.is_traffic' => ['nullable', 'boolean'],
            //            'cargos.*.cottage_number' => ['nullable', 'string', 'max:255'],
            //            'cargos.*.cottage_number_2' => ['nullable', 'string', 'max:255'],
            //            'cargos.*.driver_account_number' => ['nullable', 'string', 'max:255'],
            //            'cargos.*.container_number' => ['nullable', 'string', 'max:255'],
            //            'cargos.*.container_number_2' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function hasStatus(WaybillStatus $status): bool
    {
        return $this->string('status')->toString() === $status->value;
    }
}
