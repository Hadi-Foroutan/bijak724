<?php

namespace App\Services\Company\TransportContract;

use App\Enums\StatusEnum;
use App\Enums\TransportContractItemName;
use App\Models\Company;
use App\Models\TransportContract;
use Illuminate\Support\Facades\DB;

class DefaultTransportContractService
{
    public function createForCompany(Company $company): TransportContract
    {
        return DB::transaction(function () use ($company): TransportContract {
            $transportContract = TransportContract::query()->create([
                'company_id' => $company->id,
                'title' => 'پیشفرض',
                'contract_number' => '1',
                'contract_date' => now()->addYear()->toDateString(),
                'customer_name' => 'عمومی',
                'status' => StatusEnum::ACTIVE->value,
                'default_unknown' => true,
                'default_free' => true,
                'default_rental' => true,
                'default_owned' => true,
                'description' => null,
            ]);

            $transportContract->items()->createMany($this->items());

            return $transportContract->load('items');
        });
    }

    /** @return array<int, array<string, bool|float|string|null>> */
    private function items(): array
    {
        return array_map(function (TransportContractItemName $itemName): array {
            $typeFieldsAreEnabled = $itemName !== TransportContractItemName::BaseFreight;

            return [
                'name' => $itemName->value,
                'is_owned' => $typeFieldsAreEnabled,
                'is_rental' => $typeFieldsAreEnabled,
                'is_free' => $typeFieldsAreEnabled,
                'is_unknown' => $typeFieldsAreEnabled,
                'charge_recipient' => false,
                'primary_value' => in_array($itemName, [
                    TransportContractItemName::Commission,
                    TransportContractItemName::InsuranceVat,
                ], true) ? 10 : null,
                'secondary_value' => null,
            ];
        }, TransportContractItemName::cases());
    }
}
