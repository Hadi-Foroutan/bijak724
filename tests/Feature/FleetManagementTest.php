<?php

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->user = User::query()->forceCreate([
        'national_code' => fake()->unique()->numerify('##########'),
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => fake()->unique()->numerify('09#########'),
        'email' => fake()->unique()->safeEmail(),
        'username' => fake()->unique()->userName(),
        'password' => 'password',
    ]);

    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت تست ناوگان',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $this->driverLicenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);
    $this->loadingType = LoadingType::query()->create([
        'name' => 'کفی',
        'code' => 101,
    ]);
    $this->fleetBrand = FleetBrand::query()->create([
        'name' => 'بنز',
        'brand_code' => 10,
    ]);
    $this->fleetType = FleetType::query()->create([
        'tip_code' => 1001,
        'name' => 'اکتروس',
        'brand_code' => $this->fleetBrand->brand_code,
    ]);

    $token = $this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;

    $this->withToken($token);
});

test('it creates shows and lists fleets with shared table resources', function () {
    $response = $this->postJson('/api/user/fleets', fleetPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.smart_card_number', '1234567890')
        ->assertJsonPath('data.ownership_type', FleetOwnershipType::Owned->value)
        ->assertJsonPath('data.system_id', $this->fleetBrand->id)
        ->assertJsonPath('data.tip_code', $this->fleetType->tip_code)
        ->assertJsonPath('data.driver_license_type.name', 'پایه یک')
        ->assertJsonPath('data.loading_type.name', 'کفی')
        ->assertJsonPath('data.fleet_brand.name', 'بنز')
        ->assertJsonPath('data.fleet_type.name', 'اکتروس');

    $fleetId = $response->json('data.id');

    $this->getJson("/api/user/fleets/{$fleetId}")
        ->assertSuccessful()
        ->assertJsonPath('data.vin', 'IR123456789012345');

    $this->getJson('/api/user/fleets?search=1234567890&paginate=1')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $fleetId)
        ->assertJsonPath('data.data.0.fleet_brand.brand_code', 10);

    $this->getJson('/api/user/fleets?search=1234567890')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $fleetId)
        ->assertJsonPath('data.0.fleet_brand.brand_code', 10);
});

test('it updates and deletes a fleet', function () {
    $fleetId = $this->postJson('/api/user/fleets', fleetPayload($this))
        ->assertCreated()
        ->json('data.id');

    $otherBrand = FleetBrand::query()->create([
        'name' => 'ولوو',
        'brand_code' => 20,
    ]);
    $otherFleetType = FleetType::query()->create([
        'tip_code' => 2001,
        'name' => 'FH',
        'brand_code' => $otherBrand->brand_code,
    ]);

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'status' => StatusEnum::INACTIVE->value,
        'has_violation' => true,
        'tip_code' => $otherFleetType->tip_code,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', StatusEnum::INACTIVE->value)
        ->assertJsonPath('data.has_violation', true)
        ->assertJsonPath('data.tip_code', $otherFleetType->tip_code)
        ->assertJsonPath('data.system_id', $otherBrand->id)
        ->assertJsonPath('data.fleet_brand.name', 'ولوو');

    $this->deleteJson("/api/user/fleets/{$fleetId}")
        ->assertSuccessful();

    $this->getJson("/api/user/fleets/{$fleetId}")
        ->assertNotFound();
});

test('it finds a fleet by smart card number only inside the current company', function () {
    $this->postJson('/api/user/fleets', fleetPayload($this))->assertCreated();

    $this->getJson('/api/user/fleets/inquiry/1234567890')
        ->assertSuccessful()
        ->assertJsonPath('data.smart_card_number', '1234567890')
        ->assertJsonPath('data.fleet_type.tip_code', 1001);

    $otherCompany = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت دیگر',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $otherToken = $this->user->createToken(
        'other-company-session',
        ['company-support', "company:{$otherCompany->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    app('auth')->forgetGuards();

    $this->withToken($otherToken)
        ->getJson('/api/user/fleets/inquiry/1234567890')
        ->assertNotFound();
});

test('it derives the fleet brand from the tip code', function () {
    $otherBrand = FleetBrand::query()->create([
        'name' => 'ولوو',
        'brand_code' => 20,
    ]);
    $otherFleetType = FleetType::query()->create([
        'tip_code' => 2001,
        'name' => 'FH',
        'brand_code' => $otherBrand->brand_code,
    ]);

    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'tip_code' => $otherFleetType->tip_code,
        'system_id' => $this->fleetBrand->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.tip_code', $otherFleetType->tip_code)
        ->assertJsonPath('data.system_id', $otherBrand->id)
        ->assertJsonPath('data.fleet_brand.brand_code', $otherBrand->brand_code);
});

test('it rejects an invalid fleet tip code', function () {
    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'tip_code' => 999999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tip_code');

    $fleetTypeWithoutBrand = FleetType::query()->create([
        'tip_code' => 3001,
        'name' => 'بدون برند',
        'brand_code' => null,
    ]);

    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'tip_code' => $fleetTypeWithoutBrand->tip_code,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tip_code');
});

test('it stores an optional fleet description', function () {
    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'description' => 'توضیحات ناوگان',
    ])
        ->assertCreated()
        ->assertJsonPath('data.description', 'توضیحات ناوگان');
});

test('it requires a fleet tip code', function () {
    $payload = fleetPayload($this);
    $payload['tip_code'] = null;

    $this->postJson('/api/user/fleets', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tip_code');
});

function fleetPayload(object $test): array
{
    return [
        'smart_card_number' => '1234567890',
        'status' => StatusEnum::ACTIVE->value,
        'ownership_type' => FleetOwnershipType::Owned->value,
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'manufacture_year' => 1402,
        'driver_license_type_id' => $test->driverLicenseType->id,
        'owner_mobile' => '09121234567',
        'loading_type_id' => $test->loadingType->id,
        'insurance_policy_number' => 'INS-1001',
        'chassis_number' => 'CHASSIS-1001',
        'engine_number' => 'ENGINE-1001',
        'vin' => 'IR123456789012345',
        'tip_code' => $test->fleetType->tip_code,
        'document_date' => '2026-01-01',
        'document_number' => 'DOC-1001',
        'insurance_date' => '2026-01-02',
        'technical_inspection_valid_until' => '2027-01-01',
        'has_violation' => false,
    ];
}
