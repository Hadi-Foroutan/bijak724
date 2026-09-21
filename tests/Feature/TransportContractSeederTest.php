<?php

use App\Enums\TransportContractItemName;
use App\Models\Company;
use App\Models\TransportContract;
use Database\Seeders\TransportContractSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds complete and idempotent transport contracts for company 1000', function () {
    Company::factory()->create(['id' => 1000]);

    $this->seed(TransportContractSeeder::class);
    $this->seed(TransportContractSeeder::class);

    $contracts = TransportContract::query()
        ->where('company_id', 1000)
        ->with('items')
        ->get();

    expect($contracts)->toHaveCount(3)
        ->and($contracts->pluck('items')->flatten())->toHaveCount(30);

    foreach (['default_unknown', 'default_free', 'default_rental', 'default_owned'] as $defaultField) {
        expect($contracts->where($defaultField, true))->toHaveCount(1);
    }

    foreach ($contracts as $contract) {
        expect($contract->items)->toHaveCount(count(TransportContractItemName::cases()))
            ->and($contract->items->pluck('name')->map->value->all())
            ->toEqualCanonicalizing(array_column(TransportContractItemName::cases(), 'value'));

        foreach ($contract->items as $item) {
            expect((float) $item->primary_value)->toEqual(floor((float) $item->primary_value));
        }

        $baseFreight = $contract->items->firstWhere('name', TransportContractItemName::BaseFreight);

        expect($baseFreight)->not->toBeNull()
            ->and($baseFreight->is_owned)->toBeFalse()
            ->and($baseFreight->is_rental)->toBeFalse()
            ->and($baseFreight->is_free)->toBeFalse()
            ->and($baseFreight->is_unknown)->toBeFalse();
    }
});
