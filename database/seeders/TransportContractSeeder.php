<?php

namespace Database\Seeders;

use App\Enums\TransportContractItemName;
use App\Models\Company;
use App\Models\TransportContract;
use Illuminate\Database\Seeder;

class TransportContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $transportContract = TransportContract::query()->firstOrCreate([
                'company_id' => $company->id,
                'contract_number' => "DEMO-{$company->id}",
            ], [
                'title' => 'قرارداد حمل نمونه',
                'contract_date' => now()->toDateString(),
                'customer_name' => 'مشتری نمونه',
                'status' => 'active',
                'is_default' => false,
                'default_owned' => false,
                'default_rental' => false,
                'default_free' => false,
                'default_unknown' => true,
            ]);

            foreach (TransportContractItemName::cases() as $itemName) {
                $transportContract->items()->firstOrCreate(
                    ['name' => $itemName->value],
                    [
                        'is_owned' => false,
                        'is_rental' => false,
                        'is_free' => false,
                        'is_unknown' => true,
                        'charge_recipient' => false,
                    ],
                );
            }
        });
    }
}
