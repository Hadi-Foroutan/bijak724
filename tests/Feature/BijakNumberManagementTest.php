<?php

use App\Enums\BijakNumberStatus;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\BijakNumber\BijakNumberService;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'bijak-number-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('bijak number ranges have independent crud and inquiry endpoints', function () {
    removeDefaultBijakNumber($this);

    $response = $this->postJson('/api/user/bijak-numbers', bijakNumberPayload())
        ->assertCreated()
        ->assertJsonPath('data.status', BijakNumberStatus::Active->value)
        ->assertJsonPath('data.last_number', null)
        ->assertJsonMissingPath('data.active_slot');
    $id = $response->json('data.id');

    $this->getJson('/api/user/bijak-numbers?paginate=1')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $id);
    $this->getJson("/api/user/bijak-numbers/{$id}")
        ->assertSuccessful()
        ->assertJsonPath('data.serial_number', 'BIJAK-SER-1');
    $this->getJson('/api/user/bijak-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.bijak_number', 100)
        ->assertJsonPath('data.serial_number', 'BIJAK-SER-1');

    $this->patchJson("/api/user/bijak-numbers/{$id}", ['title' => 'عنوان جدید'])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'عنوان جدید');
    $this->deleteJson("/api/user/bijak-numbers/{$id}")->assertSuccessful();
    $this->getJson("/api/user/bijak-numbers/{$id}")->assertNotFound();
    $this->getJson('/api/user/bijak-numbers/inquiry')->assertNotFound();

    $tableName = "company_{$this->company->id}_bijak_numbers";
    expect(Schema::hasTable($tableName))->toBeTrue()
        ->and(Schema::hasIndex($tableName, "{$tableName}_active_unique"))->toBeTrue();
});

test('bijak and referral ranges store and consume numbers independently', function () {
    removeDefaultBijakNumber($this);
    removeDefaultReferralNumberForBijakTest($this);

    $this->postJson('/api/user/bijak-numbers', bijakNumberPayload())->assertCreated();
    $this->postJson('/api/user/referral-numbers', [
        'title' => 'دفتر حواله',
        'serial_number' => 'REF-SER-1',
        'from_number' => 700,
        'to_number' => 701,
    ])->assertCreated();

    $bijak = app(BijakNumberService::class)->reserveNext($this->company->id);
    $referral = app(ReferralNumberService::class)->reserveNext($this->company->id);

    expect($bijak->data['bijak_number'])->toBe(100)
        ->and($bijak->data['serial_number'])->toBe('BIJAK-SER-1')
        ->and($referral->data['referral_number'])->toBe(700)
        ->and($referral->data['serial_number'])->toBe('REF-SER-1');

    $this->getJson('/api/user/bijak-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.bijak_number', 101);
    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 701);
});

test('new companies receive separate default bijak and referral ranges', function () {
    $this->getJson('/api/user/bijak-numbers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'پیش فرض')
        ->assertJsonPath('data.0.serial_number', '1405');

    $this->getJson('/api/user/referral-numbers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'پیش فرض')
        ->assertJsonPath('data.0.serial_number', '1405');

    expect(Schema::hasTable("company_{$this->company->id}_bijak_numbers"))->toBeTrue()
        ->and(Schema::hasTable("company_{$this->company->id}_referral_numbers"))->toBeTrue()
        ->and(Schema::hasColumn("company_{$this->company->id}_referral_numbers", 'serial_number'))->toBeTrue();
});

/** @return array<string, mixed> */
function bijakNumberPayload(): array
{
    return [
        'title' => 'دفتر بیجک',
        'serial_number' => 'BIJAK-SER-1',
        'from_number' => 100,
        'to_number' => 101,
    ];
}

function removeDefaultBijakNumber(object $test): void
{
    $defaultId = $test->getJson('/api/user/bijak-numbers')
        ->assertSuccessful()
        ->json('data.0.id');

    $test->deleteJson("/api/user/bijak-numbers/{$defaultId}")->assertSuccessful();
}

function removeDefaultReferralNumberForBijakTest(object $test): void
{
    $defaultId = $test->getJson('/api/user/referral-numbers')
        ->assertSuccessful()
        ->json('data.0.id');

    $test->deleteJson("/api/user/referral-numbers/{$defaultId}")->assertSuccessful();
}
