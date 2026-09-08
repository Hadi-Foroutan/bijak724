<?php

namespace App\Http\Requests\Waybill;

use App\Http\Requests\BaseRequest;
use App\Models\Cargo;
use App\Models\Packaging;
use App\Models\TransportContract;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class StoreWaybillRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->waybillRules();
    }

    /** @return array<string, array<int, mixed>> */
    protected function waybillRules(): array
    {
        $companyDataService = app(CompanyDataService::class);
        $companyId = $this->companyId();
        $requiredWhenComplete = Rule::requiredIf(fn (): bool => ! $this->boolean('is_incomplete', true));

        return [
            ...$this->referenceRules($companyDataService, $companyId, $requiredWhenComplete),
            ...$this->documentRules($requiredWhenComplete),
            ...$this->financialRules($companyId, $requiredWhenComplete),
            ...$this->cargoRules($requiredWhenComplete),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function referenceRules(
        CompanyDataService $companyDataService,
        int $companyId,
        mixed $requiredWhenComplete,
    ): array {
        $senderExists = $companyDataService->ownedExistsRule($companyId, 'shipment_parties')
            ->where('is_sender', true);
        $receiverExists = $companyDataService->ownedExistsRule($companyId, 'shipment_parties')
            ->where('is_receiver', true);

        return [
            'sender_id' => [$requiredWhenComplete, 'nullable', 'integer', $senderExists],
            'receiver_id' => [$requiredWhenComplete, 'nullable', 'integer', $receiverExists],
            'driver1_id' => [$requiredWhenComplete, 'nullable', 'integer', $companyDataService->ownedExistsRule($companyId, 'drivers')],
            'driver2_id' => ['nullable', 'integer', 'different:driver1_id', $companyDataService->ownedExistsRule($companyId, 'drivers')],
            'fleet_id' => [$requiredWhenComplete, 'nullable', 'integer', $companyDataService->ownedExistsRule($companyId, 'fleets')],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function documentRules(mixed $requiredWhenComplete): array
    {
        return [
            'is_incomplete' => ['required', 'boolean'],
            'referral_weight' => [$requiredWhenComplete, 'nullable', 'numeric', 'min:0'],
            'quantity' => [$requiredWhenComplete, 'nullable', 'integer', 'min:1'],
            'loading_started_at' => [$requiredWhenComplete, 'nullable', 'date'],
            'loading_ended_at' => [$requiredWhenComplete, 'nullable', 'date', 'after_or_equal:loading_started_at'],
            'referral_number' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'bijak_number' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'serial_number' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'issued_at' => [$requiredWhenComplete, 'nullable', 'date'],
            'liability_insurance' => [$requiredWhenComplete, 'nullable', 'string', 'max:255'],
            'bijak_tracking_code' => ['nullable', 'string', 'max:255'],
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
            'payable_amount' => [Rule::requiredIf(fn (): bool => $this->boolean('is_fixed')), 'nullable', 'integer', 'min:0'],
            'freight_at_origin' => [$requiredWhenComplete, 'boolean'],
            'is_fixed' => [$requiredWhenComplete, 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function cargoRules(mixed $requiredWhenComplete): array
    {
        return [
            'cargos' => [$requiredWhenComplete, 'nullable', 'array', 'min:1'],
            'cargos.*.cargo_id' => ['required', 'integer', Rule::exists(Cargo::class, 'id')],
            'cargos.*.packaging_id' => ['required', 'integer', Rule::exists(Packaging::class, 'id')],
            'cargos.*.title' => ['required', 'string', 'max:255'],
            'cargos.*.description' => ['nullable', 'string'],
            'cargos.*.origin_weight' => ['required', 'numeric', 'min:0'],
            'cargos.*.value' => ['required', 'integer', 'min:0'],
            'cargos.*.quantity' => ['required', 'integer', 'min:1'],
            'cargos.*.is_traffic' => ['required', 'boolean'],
            'cargos.*.is_returned' => ['required', 'boolean'],
            'cargos.*.cottage_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.cottage_number_2' => ['nullable', 'string', 'max:255'],
            'cargos.*.driver_account_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.container_number' => ['nullable', 'string', 'max:255'],
            'cargos.*.container_number_2' => ['nullable', 'string', 'max:255'],
        ];
    }
}
