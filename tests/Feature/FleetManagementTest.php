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
use Illuminate\Support\Facades\Schema;

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
        'system_id' => $otherBrand->brand_code,
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

test('it finds a fleet by its complete plate only inside the current company', function () {
    $this->postJson('/api/user/fleets', fleetPayload($this))->assertCreated();

    $this->getJson('/api/user/fleets/inquiry?'.http_build_query(fleetPlate()))
        ->assertSuccessful()
        ->assertJsonPath('data.smart_card_number', '1234567890')
        ->assertJsonPath('data.fleet_type.tip_code', 1001);

    $this->postJson('/api/user/fleets/inquiry', fleetPlate())
        ->assertSuccessful()
        ->assertJsonPath('data.plate.first_number', '12');

    $this->getJson('/api/user/fleets/inquiry?'.http_build_query([
        ...fleetPlate(),
        'plate_fourth_number' => '68',
    ]))->assertNotFound();

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
        ->getJson('/api/user/fleets/inquiry?'.http_build_query(fleetPlate()))
        ->assertNotFound();
});

test('it requires every plate part for fleet inquiry', function () {
    $this->getJson('/api/user/fleets/inquiry?plate_first_number=12')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'plate_second_letter',
            'plate_third_number',
            'plate_fourth_number',
        ]);

    $this->postJson('/api/user/fleets/inquiry', [
        ...fleetPlate(),
        'plate_third_number' => '34',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_third_number');
});

test('it reports an inactive fleet during plate inquiry', function () {
    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'status' => StatusEnum::INACTIVE->value,
    ])->assertCreated();

    $this->getJson('/api/user/fleets/inquiry?'.http_build_query(fleetPlate()))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'ناوگان غیرفعال است.')
        ->assertJsonPath('errors.status.0', 'ناوگان غیرفعال است.');

    $this->postJson('/api/user/fleets/inquiry', fleetPlate())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'ناوگان غیرفعال است.');
});

test('it accepts up to three characters in the plate letter for create update and inquiry', function () {
    $fleetId = $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'plate_second_letter' => 'الف',
    ])->assertCreated()
        ->assertJsonPath('data.plate.second_letter', 'الف')
        ->json('data.id');

    $this->getJson('/api/user/fleets/inquiry?'.http_build_query([
        ...fleetPlate(),
        'plate_second_letter' => 'الف',
    ]))->assertSuccessful()->assertJsonPath('data.id', $fleetId);

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'plate_second_letter' => 'ابج',
    ])->assertSuccessful()->assertJsonPath('data.plate.second_letter', 'ابج');

    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'smart_card_number' => '9999999999',
        'plate_second_letter' => 'ابجد',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_second_letter');

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'plate_second_letter' => 'ابجد',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_second_letter');

    $this->postJson('/api/user/fleets/inquiry', [
        ...fleetPlate(),
        'plate_second_letter' => 'ابجد',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_second_letter');
});

test('it rejects duplicate fleet plates on create and partial update', function () {
    $firstFleetId = $this->postJson('/api/user/fleets', fleetPayload($this))
        ->assertCreated()
        ->json('data.id');

    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'smart_card_number' => '9999999999',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_first_number');

    $secondFleetId = $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'smart_card_number' => '9999999999',
        'plate_fourth_number' => '68',
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/user/fleets/{$secondFleetId}", [
        'plate_fourth_number' => '67',
    ])->assertUnprocessable()->assertJsonValidationErrors('plate_first_number');

    $this->patchJson("/api/user/fleets/{$firstFleetId}", [
        'plate_fourth_number' => '67',
    ])->assertSuccessful();

    $tableName = "company_{$this->company->id}_fleets";
    $this->assertTrue(Schema::hasIndex($tableName, "{$tableName}_plate_unique"));
});

test('it rejects a fleet type that does not belong to the selected system', function () {
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
        'system_id' => $this->fleetBrand->brand_code,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tip_code');
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

test('it rejects an invalid fleet system code', function () {
    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'system_id' => 999999,
        'tip_code' => null,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('system_id');
});

test('it stores an optional fleet description', function () {
    $this->postJson('/api/user/fleets', [
        ...fleetPayload($this),
        'description' => 'توضیحات ناوگان',
    ])
        ->assertCreated()
        ->assertJsonPath('data.description', 'توضیحات ناوگان');
});

test('it allows a system without a fleet type', function () {
    $payload = fleetPayload($this);
    $payload['tip_code'] = null;

    $this->postJson('/api/user/fleets', $payload)
        ->assertCreated()
        ->assertJsonPath('data.system_id', $this->fleetBrand->id)
        ->assertJsonPath('data.tip_code', null);
});

test('it creates a fleet with only a plate and nullable system fields', function () {
    $this->postJson('/api/user/fleets', [
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'status' => null,
        'ownership_type' => '',
        'system_id' => null,
        'tip_code' => null,
        'document_date' => [],
        'insurance_date' => '----/--/--',
        'technical_inspection_valid_until' => [
            'year' => null,
            'month' => null,
            'day' => null,
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.system_id', null)
        ->assertJsonPath('data.tip_code', null);
});

test('it allows a fleet type without a system', function () {
    $this->postJson('/api/user/fleets', [
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'tip_code' => $this->fleetType->tip_code,
    ])
        ->assertCreated()
        ->assertJsonPath('data.system_id', null)
        ->assertJsonPath('data.tip_code', $this->fleetType->tip_code);
});

test('it preserves the system and fleet type relation during updates', function () {
    $fleetId = $this->postJson('/api/user/fleets', fleetPayload($this))
        ->assertCreated()
        ->json('data.id');
    $otherBrand = FleetBrand::query()->create([
        'name' => 'ولوو',
        'brand_code' => 20,
    ]);

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'system_id' => $otherBrand->brand_code,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tip_code');

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'system_id' => null,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.system_id', null)
        ->assertJsonPath('data.tip_code', $this->fleetType->tip_code);

    $this->patchJson("/api/user/fleets/{$fleetId}", [
        'system_id' => null,
        'tip_code' => null,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.system_id', null)
        ->assertJsonPath('data.tip_code', null);
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
        'system_id' => $test->fleetBrand->brand_code,
        'tip_code' => $test->fleetType->tip_code,
        'document_date' => '2026-01-01',
        'document_number' => 'DOC-1001',
        'insurance_date' => '2026-01-02',
        'technical_inspection_valid_until' => '2027-01-01',
        'has_violation' => false,
    ];
}

function fleetPlate(): array
{
    return [
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
    ];
}
