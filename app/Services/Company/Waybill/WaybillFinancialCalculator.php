<?php

namespace App\Services\Company\Waybill;

use App\Enums\TransportContractItemName;
use App\Models\TransportContract;

class WaybillFinancialCalculator
{
    /** @param array<string, mixed> $data */
    public function calculate(int $companyId, array $data): array
    {
        $contractId = $data['transport_contract_id'] ?? null;
        $baseFreightAmount = $data['base_freight_amount'] ?? null;

        if ($contractId === null || $baseFreightAmount === null) {
            return $data;
        }

        $contract = TransportContract::query()
            ->where('company_id', $companyId)
            ->with('items')
            ->findOrFail($contractId);
        $items = $contract->items->keyBy(fn ($item): string => $item->name->value);
        $baseFreightAmount = (int) $baseFreightAmount;

        $percentageAmount = function (TransportContractItemName $itemName) use ($items, $baseFreightAmount): int {
            $percentage = (float) ($items->get($itemName->value)?->primary_value ?? 0);

            return (int) round($baseFreightAmount * $percentage / 100);
        };

        $data['advance_freight_amount'] = $percentageAmount(TransportContractItemName::AdvanceFreight);
        $data['weighbridge_amount'] = $this->optionalAmount($data, 'weighbridge_amount', $percentageAmount(TransportContractItemName::WeighbridgeCost));
        $data['loading_amount'] = $this->optionalAmount($data, 'loading_amount', $percentageAmount(TransportContractItemName::LoadingCost));
        $data['warehousing_amount'] = $this->optionalAmount($data, 'warehousing_amount', $percentageAmount(TransportContractItemName::Warehousing));
        $data['commission_amount'] = $percentageAmount(TransportContractItemName::Commission);
        $data['insurance_amount'] = $percentageAmount(TransportContractItemName::InsurancePremium);
        $data['insurance_tax_amount'] = $percentageAmount(TransportContractItemName::InsuranceVat);
        $data['driver_receivable_amount'] = $this->driverReceivableAmount($data);

        if (! ($data['is_fixed'] ?? false)) {
            $data['payable_amount'] = max(
                0,
                $baseFreightAmount
                    + $data['driver_receivable_amount']
                    + $data['commission_amount']
                    - $data['advance_freight_amount'],
            );
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function optionalAmount(array $data, string $field, int $calculatedAmount): ?int
    {
        return ($data[$field] ?? null) === null ? null : $calculatedAmount;
    }

    /** @param array<string, mixed> $data */
    private function driverReceivableAmount(array $data): int
    {
        return (int) collect([
            $data['weighbridge_amount'],
            $data['loading_amount'],
            $data['warehousing_amount'],
            $data['detention_amount'] ?? null,
            $data['insurance_amount'],
            $data['insurance_tax_amount'],
        ])->sum();
    }
}
