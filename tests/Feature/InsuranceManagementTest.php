<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Cargo;
use App\Models\CargoGroup;
use App\Models\CargoGroupCargo;
use App\Models\Company;
use App\Models\Insurance;
use App\Models\InsuranceCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->insuranceCompany = InsuranceCompany::query()->forceCreate([
        'name' => 'بیمه ایران',
        'org_code' => 101,
        'status' => 'active',
    ]);
    $this->defaultCargoGroup = CargoGroup::query()->where('group_number', 1)->firstOrFail();
    $this->cargoGroup = CargoGroup::query()->where('group_number', 2)->firstOrFail();

    $token = $this->user->createToken(
        'insurance-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);
});

test('insurance crud is company scoped searchable and keeps one default', function () {
    $otherCompanyInsurance = Insurance::factory()->create(['is_default' => true]);

    $firstId = $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-100', true))
        ->assertCreated()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.insurance_company.name', 'بیمه ایران')
        ->assertJsonPath('data.is_default', true)
        ->json('data.id');

    $secondId = $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-101', true))
        ->assertCreated()
        ->json('data.id');

    expect(Insurance::query()->findOrFail($firstId)->is_default)->toBeFalse()
        ->and(Insurance::query()->findOrFail($secondId)->is_default)->toBeTrue()
        ->and($otherCompanyInsurance->fresh()->is_default)->toBeTrue();

    $this->getJson('/api/user/insurances?search=INS-101&paginate=true&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $secondId);

    $this->patchJson("/api/user/insurances/{$secondId}", ['title' => 'بیمه ویرایش‌شده'])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'بیمه ویرایش‌شده');

    $this->patchJson("/api/user/insurances/{$secondId}", ['start_date' => '2027-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');

    $this->deleteJson("/api/user/insurances/{$firstId}")->assertSuccessful();
    $this->assertDatabaseMissing('insurances', ['id' => $firstId]);
});

test('insurance tariff accepts fixed percentage or both and is company scoped', function () {
    $insuranceId = $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-200'))
        ->assertCreated()
        ->json('data.id');

    $tariffId = $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => $this->cargoGroup->id,
        'cargo_value_from' => 1000000,
        'cargo_value_to' => 5000000,
        'fixed_premium' => 250000,
        'premium_percentage' => 1.25,
        'excess_amount' => 5000000,
        'description' => 'تعرفه گروه عمومی',
    ])
        ->assertCreated()
        ->assertJsonPath('data.cargo_group.name', 'گروه 2')
        ->assertJsonPath('data.cargo_group.group_number', 2)
        ->assertJsonPath('data.fixed_premium', 250000)
        ->assertJsonPath('data.premium_percentage', 1.25)
        ->json('data.id');

    $this->patchJson("/api/user/insurances/{$insuranceId}/tariffs/{$tariffId}", [
        'description' => 'تعرفه اصلاح‌شده',
    ])->assertSuccessful()->assertJsonPath('data.description', 'تعرفه اصلاح‌شده');

    $this->patchJson("/api/user/insurances/{$insuranceId}/tariffs/{$tariffId}", [
        'fixed_premium' => null,
    ])->assertSuccessful()->assertJsonPath('data.fixed_premium', null);

    $this->patchJson("/api/user/insurances/{$insuranceId}/tariffs/{$tariffId}", [
        'premium_percentage' => null,
    ])->assertUnprocessable()->assertJsonValidationErrors(['fixed_premium', 'premium_percentage']);

    $this->getJson("/api/user/insurances/{$insuranceId}/tariffs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => $this->cargoGroup->id,
        'cargo_value_from' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors(['cargo_group_id', 'fixed_premium', 'premium_percentage']);

    $otherInsurance = Insurance::factory()->create();
    $this->getJson("/api/user/insurances/{$otherInsurance->id}")->assertNotFound();
    $this->postJson("/api/user/insurances/{$otherInsurance->id}/tariffs", [
        'cargo_group_id' => $this->cargoGroup->id,
        'cargo_value_from' => 0,
        'fixed_premium' => 100,
    ])->assertNotFound();

    $this->deleteJson("/api/user/insurances/{$insuranceId}/tariffs/{$tariffId}")->assertSuccessful();
    $this->assertDatabaseMissing('insurance_tariffs', ['id' => $tariffId]);
});

test('insurance inquiry calculates and sums cargo fees using company cargo groups', function () {
    $insuranceId = $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-400'))
        ->assertCreated()
        ->json('data.id');
    $groupedCargo = Cargo::query()->create(['name' => 'محموله گروه دوم', 'code' => 4001]);
    $defaultCargo = Cargo::query()->create(['name' => 'محموله گروه پیش‌فرض', 'code' => 4002]);

    CargoGroupCargo::query()->create([
        'company_id' => $this->company->id,
        'cargo_group_id' => $this->cargoGroup->id,
        'cargo_id' => $groupedCargo->id,
    ]);

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => $this->cargoGroup->id,
        'cargo_value_from' => 50000,
        'cargo_value_to' => 60000,
        'fixed_premium' => 250,
        'premium_percentage' => 2,
        'excess_amount' => 1000,
    ])->assertCreated();

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => $this->defaultCargoGroup->id,
        'cargo_value_from' => 0,
        'cargo_value_to' => 100000,
        'fixed_premium' => 100,
        'premium_percentage' => 1,
        'excess_amount' => 0,
    ])->assertCreated();

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => null,
        'cargo_value_from' => 0,
        'cargo_value_to' => 100000,
        'fixed_premium' => 999,
    ])->assertCreated();

    $this->postJson('/api/user/insurances/inquiry', [
        'insurance_id' => $insuranceId,
        'cargos' => [
            ['id' => $groupedCargo->id, 'value' => 11000],
            ['id' => $defaultCargo->id, 'value' => 10000],
        ],
    ])->assertSuccessful()->assertJsonPath('data.fee_amount', 650);
});

test('insurance inquiry falls back to an ungrouped tariff matching the cargo value range', function () {
    $insuranceId = $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-401'))
        ->assertCreated()
        ->json('data.id');
    $cargo = Cargo::query()->create(['name' => 'محموله تعرفه عمومی', 'code' => 4010]);

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => null,
        'cargo_value_from' => 0,
        'cargo_value_to' => 9999,
        'fixed_premium' => 10,
    ])->assertCreated()->assertJsonPath('data.cargo_group_id', null);

    $this->postJson("/api/user/insurances/{$insuranceId}/tariffs", [
        'cargo_group_id' => null,
        'cargo_value_from' => 10000,
        'cargo_value_to' => 20000,
        'fixed_premium' => 100,
        'premium_percentage' => 2,
        'excess_amount' => 5000,
    ])->assertCreated();

    $this->postJson('/api/user/insurances/inquiry', [
        'insurance_id' => $insuranceId,
        'cargos' => [['id' => $cargo->id, 'value' => 15000]],
    ])->assertSuccessful()->assertJsonPath('data.fee_amount', 300);
});

test('insurance inquiry validates ownership and reports a missing cargo tariff', function () {
    $insurance = Insurance::factory()->for($this->company)->create();
    $otherInsurance = Insurance::factory()->create();
    $cargo = Cargo::query()->create(['name' => 'محموله بدون تعرفه', 'code' => 4020]);

    $this->postJson('/api/user/insurances/inquiry', [
        'insurance_id' => $otherInsurance->id,
        'cargos' => [['id' => $cargo->id, 'value' => 10000]],
    ])->assertUnprocessable()->assertJsonValidationErrors('insurance_id');

    $this->postJson('/api/user/insurances/inquiry', [
        'insurance_id' => $insurance->id,
        'cargos' => [['id' => $cargo->id, 'value' => 10000]],
    ])->assertUnprocessable()
        ->assertJsonPath('errors.error.0', "تعرفه بیمه برای محموله با شناسه {$cargo->id} یافت نشد.");
});

test('insurance validation rejects invalid dates and duplicate company contract numbers', function () {
    $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-300'))->assertCreated();

    $this->postJson('/api/user/insurances', insurancePayload($this->insuranceCompany->id, 'INS-300'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('contract_number');

    $this->postJson('/api/user/insurances', [
        ...insurancePayload($this->insuranceCompany->id, 'INS-301'),
        'end_date' => '2025-12-31',
    ])->assertUnprocessable()->assertJsonValidationErrors('end_date');
});

/** @return array<string, mixed> */
function insurancePayload(int $insuranceCompanyId, string $contractNumber, bool $isDefault = false): array
{
    return [
        'insurance_company_id' => $insuranceCompanyId,
        'title' => "قرارداد {$contractNumber}",
        'contract_number' => $contractNumber,
        'is_default' => $isDefault,
        'status' => 'active',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'description' => 'توضیحات بیمه',
        'representative_first_name' => 'علی',
        'representative_last_name' => 'احمدی',
        'representative_mobile' => '09121234567',
        'representative_phone' => '02112345678',
        'representative_fax' => '02112345679',
        'representative_email' => 'agent@example.com',
        'representative_address' => 'تهران',
    ];
}
