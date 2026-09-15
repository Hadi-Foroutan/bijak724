<?php

use App\Enums\ReferralNumberStatus;
use App\Helpers\ServiceResult;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'referral-number-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('it creates lists shows updates and deletes referral number ranges', function () {
    removeDefaultReferralNumber($this);
    $response = $this->postJson('/api/user/referral-numbers', referralNumberPayload())
        ->assertCreated()
        ->assertJsonPath('data.status', ReferralNumberStatus::Active->value)
        ->assertJsonPath('data.last_number', 100)
        ->assertJsonMissingPath('data.active_slot');
    $id = $response->json('data.id');

    $this->getJson('/api/user/referral-numbers?paginate=1')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $id);
    $this->getJson("/api/user/referral-numbers/{$id}")
        ->assertSuccessful()
        ->assertJsonPath('data.serial_number', 'SER-1');
    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 101)
        ->assertJsonPath('data.serial_number', 'SER-1');

    $this->patchJson("/api/user/referral-numbers/{$id}", ['title' => 'عنوان جدید'])
        ->assertSuccessful()->assertJsonPath('data.title', 'عنوان جدید');
    $this->deleteJson("/api/user/referral-numbers/{$id}")->assertSuccessful();
    $this->getJson("/api/user/referral-numbers/{$id}")->assertNotFound();
    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();

    $tableName = "company_{$this->company->id}_referral_numbers";
    expect(Schema::hasIndex($tableName, "{$tableName}_active_unique"))->toBeTrue();
});

test('it allows only one active referral number range per company', function () {
    removeDefaultReferralNumber($this);
    $activeId = $this->postJson('/api/user/referral-numbers', referralNumberPayload())
        ->assertCreated()->json('data.id');

    $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'serial_number' => 'SER-2',
    ])->assertUnprocessable()->assertJsonValidationErrors('status');

    $inactiveId = $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'serial_number' => 'SER-2',
        'status' => ReferralNumberStatus::Inactive->value,
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/user/referral-numbers/{$inactiveId}", [
        'status' => ReferralNumberStatus::Active->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('status');

    $this->patchJson("/api/user/referral-numbers/{$activeId}", [
        'status' => ReferralNumberStatus::Inactive->value,
    ])->assertSuccessful();

    $this->patchJson("/api/user/referral-numbers/{$inactiveId}", [
        'status' => ReferralNumberStatus::Active->value,
    ])->assertSuccessful();
});

test('active referral numbers are isolated between companies', function () {
    removeDefaultReferralNumber($this);
    $this->postJson('/api/user/referral-numbers', referralNumberPayload())->assertCreated();

    $otherCompany = Company::factory()->create();
    $otherToken = $this->user->createToken(
        'other-referral-number-session',
        ['company-support', "company:{$otherCompany->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    app('auth')->forgetGuards();
    $this->withToken($otherToken);

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 100001)
        ->assertJsonPath('data.serial_number', '1405');
    removeDefaultReferralNumber($this);
    $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'serial_number' => 'OTHER-SER',
    ])->assertCreated();

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.serial_number', 'OTHER-SER');
});

test('it validates the range and recognizes an already consumed range', function () {
    removeDefaultReferralNumber($this);
    $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'from_number' => 200,
        'to_number' => 100,
    ])->assertUnprocessable()->assertJsonValidationErrors('to_number');

    $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'last_number' => 98,
    ])->assertUnprocessable()->assertJsonValidationErrors('to_number');

    $id = $this->postJson('/api/user/referral-numbers', [
        ...referralNumberPayload(),
        'last_number' => 101,
    ])->assertCreated()
        ->assertJsonPath('data.status', ReferralNumberStatus::Completed->value)
        ->json('data.id');

    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();
    $this->patchJson("/api/user/referral-numbers/{$id}", ['status' => 'active'])
        ->assertSuccessful()->assertJsonPath('data.status', ReferralNumberStatus::Completed->value);
});

test('new companies receive an active default range with 899999 available numbers', function () {
    $this->getJson('/api/user/referral-numbers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'پیشفرض')
        ->assertJsonPath('data.0.serial_number', '1405')
        ->assertJsonPath('data.0.from_number', 100000)
        ->assertJsonPath('data.0.to_number', 999999)
        ->assertJsonPath('data.0.last_number', 100000)
        ->assertJsonPath('data.0.status', ReferralNumberStatus::Active->value);

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 100001)
        ->assertJsonPath('data.serial_number', '1405');

    expect(999999 - 100000)->toBe(899999);
});

test('branches receive their own default range in the shared company table', function () {
    $branch = Company::factory()->create([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
    ]);
    $tableName = "company_{$this->company->id}_referral_numbers";

    expect(DB::table($tableName)
        ->where('owner_company_id', $this->company->id)->count())->toBe(1)
        ->and(DB::table($tableName)
            ->where('owner_company_id', $branch->id)->count())->toBe(1);

    $branchToken = $this->user->createToken(
        'branch-referral-number-session',
        ['company-support', "company:{$branch->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    app('auth')->forgetGuards();
    $this->withToken($branchToken)
        ->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 100001);
});

test('default creation is repeatable and does not replace an existing range', function () {
    $service = app(ReferralNumberService::class);
    $defaultId = $this->getJson('/api/user/referral-numbers')->json('data.0.id');

    expect($service->ensureDefaultForCompany($this->company->id))
        ->toBeInstanceOf(ServiceResult::class);
    $this->getJson('/api/user/referral-numbers')->assertJsonCount(1, 'data');

    $this->deleteJson("/api/user/referral-numbers/{$defaultId}")->assertSuccessful();
    $result = $service->ensureDefaultForCompany($this->company->id);
    expect($result)->toBeInstanceOf(ServiceResult::class)
        ->and($result->data->serial_number)->toBe('1405');
    $this->getJson('/api/user/referral-numbers')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.last_number', 100000);
});

/** @return array<string, mixed> */
function referralNumberPayload(): array
{
    return [
        'title' => 'دفتر حواله',
        'serial_number' => 'SER-1',
        'from_number' => 100,
        'to_number' => 101,
    ];
}

function removeDefaultReferralNumber(object $test): void
{
    $defaultId = $test->getJson('/api/user/referral-numbers')
        ->assertSuccessful()
        ->json('data.0.id');

    $test->deleteJson("/api/user/referral-numbers/{$defaultId}")->assertSuccessful();
}
