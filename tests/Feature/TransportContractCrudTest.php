<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\TransportContract;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $token = $this->user->createToken(
        'transport-contract-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);
});

test('transport contracts are company scoped and replace the company default contract', function () {
    $otherCompanyDefault = TransportContract::factory()->create(['is_default' => true]);

    $firstId = $this->postJson('/api/user/transport-contracts', transportContractPayload('TC-100', true))
        ->assertCreated()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.is_default', true)
        ->assertJsonPath('data.items.0.name_label', 'کرایه پایه')
        ->assertJsonPath('data.items.0.editable_fields.is_owned', false)
        ->assertJsonPath('data.items.0.editable_fields.is_rental', false)
        ->assertJsonPath('data.items.0.editable_fields.is_free', false)
        ->assertJsonPath('data.items.0.editable_fields.is_unknown', false)
        ->assertJsonPath('data.items.0.editable_fields.primary_value', true)
        ->assertJsonPath('data.items.0.primary_value_label', 'کرایه هر تن')
        ->assertJsonPath('data.items.0.secondary_value_label', 'کرایه ثابت')
        ->assertJsonPath('data.items.7.primary_value_label', 'درصد از بیمه')
        ->assertJsonPath('data.items.7.secondary_value_label', 'مقدار ثابت از تخفیف')
        ->json('data.id');

    $secondId = $this->postJson('/api/user/transport-contracts', transportContractPayload('TC-101', true))
        ->assertCreated()
        ->json('data.id');

    expect(TransportContract::query()->findOrFail($firstId)->is_default)->toBeFalse()
        ->and(TransportContract::query()->findOrFail($secondId)->is_default)->toBeTrue()
        ->and($otherCompanyDefault->fresh()->is_default)->toBeTrue()
        ->and(TransportContract::query()->where('company_id', $this->company->id)->where('is_default', true)->count())->toBe(1);

    $this->getJson('/api/user/transport-contracts?search=TC-101')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $secondId);

    $updatedItems = transportContractItems();
    $updatedItems[5] = [
        ...$updatedItems[5],
        'is_owned' => true,
        'is_rental' => true,
        'is_free' => true,
        'is_unknown' => true,
        'charge_recipient' => true,
        'primary_value' => 150000,
        'secondary_value' => 25000,
    ];

    $this->patchJson("/api/user/transport-contracts/{$secondId}", [
        'title' => 'قرارداد ویرایش‌شده',
        'items' => $updatedItems,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'قرارداد ویرایش‌شده')
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('data.items.5.name', 'commission')
        ->assertJsonPath('data.items.5.is_owned', true)
        ->assertJsonPath('data.items.5.is_rental', true)
        ->assertJsonPath('data.items.5.is_free', true)
        ->assertJsonPath('data.items.5.is_unknown', true)
        ->assertJsonPath('data.items.5.charge_recipient', true);

    $this->deleteJson("/api/user/transport-contracts/{$firstId}")->assertSuccessful();
    $this->assertDatabaseMissing('transport_contracts', ['id' => $firstId]);
    $this->assertDatabaseMissing('transport_contract_items', ['transport_contract_id' => $firstId]);
});

test('transport contract options expose all item names types and dynamic value labels', function () {
    $this->getJson('/api/user/transport-contracts/options')
        ->assertSuccessful()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonCount(4, 'data.types')
        ->assertJsonPath('data.items.0.value', 'base_freight')
        ->assertJsonPath('data.items.0.editable_fields.is_owned', false)
        ->assertJsonPath('data.items.0.editable_fields.is_unknown', false)
        ->assertJsonPath('data.items.0.primary_value_label', 'کرایه هر تن')
        ->assertJsonPath('data.items.1.editable_fields.is_owned', true)
        ->assertJsonPath('data.items.1.editable_fields.is_unknown', true)
        ->assertJsonPath('data.items.7.value', 'insurance_premium')
        ->assertJsonPath('data.items.7.secondary_value_label', 'مقدار ثابت از تخفیف')
        ->assertJsonPath('data.types.0.field', 'is_owned')
        ->assertJsonPath('data.types.3.field', 'is_unknown');
});

test('transport contract validation rejects invalid and duplicate items', function () {
    $this->postJson('/api/user/transport-contracts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title', 'contract_number', 'contract_date', 'customer_name', 'status',
            'is_default', 'default_owned', 'default_rental', 'default_free',
            'default_unknown', 'items',
        ]);

    $missingItemPayload = transportContractPayload('TC-200', false);
    array_pop($missingItemPayload['items']);
    $this->postJson('/api/user/transport-contracts', $missingItemPayload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    $duplicateItemPayload = transportContractPayload('TC-201', false);
    $duplicateItemPayload['items'][9]['name'] = 'base_freight';
    $this->postJson('/api/user/transport-contracts', $duplicateItemPayload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.9.name');

    $id = $this->postJson('/api/user/transport-contracts', transportContractPayload('TC-202', false))
        ->assertCreated()
        ->json('data.id');
    $this->putJson("/api/user/transport-contracts/{$id}", ['title' => 'ویرایش ناقص'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    $incompleteItems = transportContractItems();
    array_pop($incompleteItems);
    $this->patchJson("/api/user/transport-contracts/{$id}", ['items' => $incompleteItems])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    foreach (['is_owned', 'is_rental', 'is_free', 'is_unknown'] as $field) {
        $invalidBaseFreightPayload = transportContractPayload("TC-{$field}", false);
        $invalidBaseFreightPayload['items'][0][$field] = true;
        $this->postJson('/api/user/transport-contracts', $invalidBaseFreightPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    $invalidUpdateItems = transportContractItems();
    $invalidUpdateItems[0]['is_owned'] = true;
    $this->patchJson("/api/user/transport-contracts/{$id}", ['items' => $invalidUpdateItems])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');
});

test('transport contracts cannot be accessed by another company', function () {
    $otherContract = TransportContract::factory()->create();

    $this->getJson("/api/user/transport-contracts/{$otherContract->id}")->assertNotFound();
    $this->patchJson("/api/user/transport-contracts/{$otherContract->id}", ['title' => 'غیرمجاز'])->assertNotFound();
    $this->deleteJson("/api/user/transport-contracts/{$otherContract->id}")->assertNotFound();
});

/** @return array<string, mixed> */
function transportContractPayload(string $contractNumber, bool $isDefault): array
{
    return [
        'title' => 'قرارداد حمل مشتری نمونه',
        'contract_number' => $contractNumber,
        'contract_date' => '2026-09-07',
        'customer_name' => 'شرکت مشتری',
        'status' => 'active',
        'is_default' => $isDefault,
        'default_owned' => true,
        'default_rental' => false,
        'default_free' => true,
        'default_unknown' => false,
        'description' => 'توضیحات قرارداد',
        'items' => transportContractItems(),
    ];
}

/** @return array<int, array<string, mixed>> */
function transportContractItems(): array
{
    $names = [
        'base_freight', 'loading_cost', 'weighbridge_cost', 'warehousing',
        'unloading_cost', 'commission', 'excess_tonnage', 'insurance_premium',
        'insurance_vat', 'advance_freight',
    ];

    return array_map(function (string $name): array {
        return [
            'name' => $name,
            'is_owned' => $name !== 'base_freight',
            'is_rental' => false,
            'is_free' => $name !== 'base_freight',
            'is_unknown' => false,
            'charge_recipient' => $name === 'insurance_premium',
            'primary_value' => $name === 'insurance_premium' ? 2.5 : 1200000,
            'secondary_value' => $name === 'insurance_premium' ? 100000 : 500000,
        ];
    }, $names);
}
