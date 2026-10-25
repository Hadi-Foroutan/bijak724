<?php

use App\Enums\WaybillStatus;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\TechnicalManager;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->withToken($this->user->createToken(
        'technical-managers',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

function technicalManagerPayload(array $overrides = []): array
{
    return [
        'full_name' => 'علی مدیر فنی',
        'national_code' => '0012345678',
        'mobile' => '09121234567',
        'status' => 'active',
        'daily_limit' => 5,
        ...$overrides,
    ];
}

test('a company manages its own technical managers', function () {
    $id = $this->postJson('/api/user/technical-managers', technicalManagerPayload())
        ->assertCreated()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.full_name', 'علی مدیر فنی')
        ->assertJsonPath('data.daily_limit', 5)
        ->assertJsonPath('data.issued_today', 0)
        ->assertJsonPath('data.remaining_today', 5)
        ->json('data.id');

    $this->getJson("/api/user/technical-managers/{$id}")->assertSuccessful();

    $this->patchJson("/api/user/technical-managers/{$id}", ['daily_limit' => 8, 'status' => 'inactive'])
        ->assertSuccessful()
        ->assertJsonPath('data.daily_limit', 8)
        ->assertJsonPath('data.status', 'inactive');

    $this->getJson('/api/user/technical-managers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->deleteJson("/api/user/technical-managers/{$id}")->assertSuccessful();
    $this->assertDatabaseMissing('technical_managers', ['id' => $id]);
});

test('technical managers are scoped to the current company', function () {
    $other = TechnicalManager::factory()->create();

    $this->getJson('/api/user/technical-managers')->assertSuccessful()->assertJsonCount(0, 'data');
    $this->getJson("/api/user/technical-managers/{$other->id}")->assertNotFound();
    $this->patchJson("/api/user/technical-managers/{$other->id}", ['daily_limit' => 1])->assertNotFound();
    $this->deleteJson("/api/user/technical-managers/{$other->id}")->assertNotFound();

    // کد ملی فقط داخل یک شرکت یکتاست.
    $this->postJson('/api/user/technical-managers', technicalManagerPayload(['national_code' => $other->national_code]))
        ->assertCreated();
});

test('technical manager validation', function () {
    $this->postJson('/api/user/technical-managers', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['full_name', 'national_code', 'mobile', 'status', 'daily_limit']);

    $this->postJson('/api/user/technical-managers', technicalManagerPayload(['mobile' => '123', 'national_code' => '12', 'daily_limit' => -1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mobile', 'national_code', 'daily_limit']);

    $id = $this->postJson('/api/user/technical-managers', technicalManagerPayload())->assertCreated()->json('data.id');

    $this->postJson('/api/user/technical-managers', technicalManagerPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['national_code']);

    // نگه داشتن کد ملی خودش در ویرایش مجاز است.
    $this->patchJson("/api/user/technical-managers/{$id}", ['national_code' => '0012345678'])->assertSuccessful();
});

test('a daily limit of zero is allowed and shows no remaining quota', function () {
    $this->postJson('/api/user/technical-managers', technicalManagerPayload(['daily_limit' => 0]))
        ->assertCreated()
        ->assertJsonPath('data.remaining_today', 0);
});

test('a technical manager used by an issued waybill cannot be deleted', function () {
    $manager = TechnicalManager::factory()->for($this->company)->create();

    Waybill::createForCompany($this->company->id, [
        'status' => WaybillStatus::Completed->value,
        'technical_manager_id' => $manager->id,
        'issued_at' => now(),
    ]);

    $this->deleteJson("/api/user/technical-managers/{$manager->id}")->assertUnprocessable();
    $this->assertDatabaseHas('technical_managers', ['id' => $manager->id]);
});

test('the list shows how many waybills were issued today', function () {
    $manager = TechnicalManager::factory()->for($this->company)->create(['daily_limit' => 3]);

    foreach ([now(), now()->subDay()] as $issuedAt) {
        Waybill::createForCompany($this->company->id, [
            'status' => WaybillStatus::Completed->value,
            'technical_manager_id' => $manager->id,
            'issued_at' => $issuedAt,
        ]);
    }

    $this->getJson('/api/user/technical-managers')
        ->assertSuccessful()
        ->assertJsonPath('data.0.issued_today', 1)
        ->assertJsonPath('data.0.remaining_today', 2);
});
