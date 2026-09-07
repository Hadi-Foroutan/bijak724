<?php

use App\Enums\CompanyParentEnum;
use App\Enums\TransportContractItemName;
use App\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it creates a universal default transport contract for every new company', function () {
    $nextYearDate = now()->addYear()->toDateString();
    $company = Company::factory()->create();
    $transportContract = $company->transportContracts()->with('items')->sole();

    expect($transportContract->title)->toBe('پیشفرض')
        ->and($transportContract->contract_number)->toBe('1')
        ->and($transportContract->contract_date->toDateString())->toBe($nextYearDate)
        ->and($transportContract->customer_name)->toBe('عمومی')
        ->and($transportContract->default_unknown)->toBeTrue()
        ->and($transportContract->default_free)->toBeTrue()
        ->and($transportContract->default_rental)->toBeTrue()
        ->and($transportContract->default_owned)->toBeTrue()
        ->and($transportContract->items)->toHaveCount(count(TransportContractItemName::cases()));

    foreach ($transportContract->items as $item) {
        $hasTenPercent = in_array($item->name, [
            TransportContractItemName::Commission,
            TransportContractItemName::InsuranceVat,
        ], true);

        expect($item->primary_value)->toBe($hasTenPercent ? '10.0000' : null)
            ->and($item->secondary_value)->toBeNull();

        if ($item->name === TransportContractItemName::BaseFreight) {
            expect($item->is_owned)->toBeFalse()
                ->and($item->is_rental)->toBeFalse()
                ->and($item->is_free)->toBeFalse()
                ->and($item->is_unknown)->toBeFalse();
        } else {
            expect($item->is_owned)->toBeTrue()
                ->and($item->is_rental)->toBeTrue()
                ->and($item->is_free)->toBeTrue()
                ->and($item->is_unknown)->toBeTrue();
        }
    }

    $branch = Company::factory()->create([
        'parent_id' => $company->id,
        'parent_type' => CompanyParentEnum::BRANCH->value,
    ]);

    expect($branch->transportContracts()->count())->toBe(1);
});
