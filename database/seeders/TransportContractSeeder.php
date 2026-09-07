<?php

namespace Database\Seeders;

use App\Enums\TransportContractItemName;
use App\Enums\TransportContractItemType;
use App\Models\Company;
use App\Models\TransportContract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransportContractSeeder extends Seeder
{
    private const COMPANY_ID = 1000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->findOrFail(self::COMPANY_ID);

        DB::transaction(function () use ($company): void {
            foreach (TransportContractItemType::cases() as $type) {
                TransportContract::query()
                    ->where('company_id', $company->id)
                    ->where($type->defaultField(), true)
                    ->update([$type->defaultField() => false]);
            }

            foreach ($this->contracts() as $contractData) {
                $items = $contractData['items'];
                unset($contractData['items']);

                $transportContract = TransportContract::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'contract_number' => $contractData['contract_number'],
                    ],
                    $contractData,
                );

                foreach ($items as $item) {
                    $transportContract->items()->updateOrCreate(
                        ['name' => $item['name']],
                        $item,
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function contracts(): array
    {
        return [
            [
                'title' => 'قرارداد حمل پیش‌فرض شرکت 1000',
                'contract_number' => 'TC-1000-001',
                'contract_date' => '2026-03-21',
                'customer_name' => 'مشتری اصلی شرکت 1000',
                'status' => 'active',
                'default_owned' => true,
                'default_rental' => false,
                'default_free' => false,
                'default_unknown' => true,
                'description' => 'قرارداد پیش‌فرض حمل برای شرکت 1000',
                'items' => $this->items(),
            ],
            [
                'title' => 'قرارداد حمل مشتری ویژه شرکت 1000',
                'contract_number' => 'TC-1000-002',
                'contract_date' => '2026-06-22',
                'customer_name' => 'مشتری ویژه شرکت 1000',
                'status' => 'active',
                'default_owned' => false,
                'default_rental' => true,
                'default_free' => true,
                'default_unknown' => false,
                'description' => 'قرارداد حمل ویژه با نرخ‌های توافقی',
                'items' => $this->items(1.15),
            ],
        ];
    }

    /**
     * @return array<int, array<string, bool|float|string>>
     */
    private function items(float $rateMultiplier = 1): array
    {
        $typeFields = [
            'is_owned' => true,
            'is_rental' => true,
            'is_free' => true,
            'is_unknown' => false,
        ];

        return [
            [
                'name' => TransportContractItemName::BaseFreight->value,
                'is_owned' => false,
                'is_rental' => false,
                'is_free' => false,
                'is_unknown' => false,
                'charge_recipient' => false,
                'primary_value' => round(12 * $rateMultiplier),
                'secondary_value' => 500_000 * $rateMultiplier,
            ],
            $this->item(TransportContractItemName::LoadingCost, 3, 50_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::WeighbridgeCost, 1, 20_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::Warehousing, 4, 75_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::UnloadingCost, 2.5, 50_000, $rateMultiplier, $typeFields, true),
            $this->item(TransportContractItemName::Commission, 1.5, 25_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::ExcessTonnage, 2, 30_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::InsurancePremium, 2.5, 100_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::InsuranceVat, 0.9, 10_000, $rateMultiplier, $typeFields),
            $this->item(TransportContractItemName::AdvanceFreight, 5, 500_000, $rateMultiplier, $typeFields),
        ];
    }

    /**
     * @param  array{is_owned: bool, is_rental: bool, is_free: bool, is_unknown: bool}  $typeFields
     * @return array{name: string, is_owned: bool, is_rental: bool, is_free: bool, is_unknown: bool, charge_recipient: bool, primary_value: float, secondary_value: float}
     */
    private function item(
        TransportContractItemName $name,
        float $primaryValue,
        float $secondaryValue,
        float $rateMultiplier,
        array $typeFields,
        bool $chargeRecipient = false,
    ): array {
        return [
            'name' => $name->value,
            ...$typeFields,
            'charge_recipient' => $chargeRecipient,
            'primary_value' => round($primaryValue * $rateMultiplier),
            'secondary_value' => $secondaryValue * $rateMultiplier,
        ];
    }
}
