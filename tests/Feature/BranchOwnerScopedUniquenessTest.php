<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use App\Models\User;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->parent = Company::query()->forceCreate([
        'parent_type' => 'original', 'panel_code' => '81001', 'organization_code' => 'ORG-81001',
        'name' => 'شرکت سرپرست', 'national_code' => '81000000001', 'city_code' => 1101,
    ]);
    $this->branch = Company::query()->forceCreate([
        'parent_id' => $this->parent->id, 'parent_type' => 'branch', 'panel_code' => '81002',
        'organization_code' => 'ORG-81002', 'name' => 'شعبه تبریز', 'national_code' => '81000000002',
        'city_code' => 1101,
    ]);

    $actor = fn (Company $company, string $username) => User::query()->forceCreate([
        'company_id' => $company->id, 'national_code' => fake()->unique()->numerify('##########'),
        'first_name' => 'کاربر', 'last_name' => $username, 'phone' => fake()->unique()->numerify('09#########'),
        'username' => $username, 'password' => 'password',
    ]);
    $this->parentToken = $actor($this->parent, 'parent-user')->createToken(
        'parent', ['company-support', "company:{$this->parent->id}"], now()->addMinutes(30),
    )->plainTextToken;
    $this->branchToken = $actor($this->branch, 'branch-user')->createToken(
        'branch', ['company-support', "company:{$this->branch->id}"], now()->addMinutes(30),
    )->plainTextToken;

    // Sanctum caches the authenticated user inside one test, so switching tokens needs a fresh guard.
    $this->as = function (string $token) {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    };

    $this->licenseType = DriverLicenseType::query()->create(['name' => 'پایه یک', 'code' => 1]);
    $this->driverPayload = fn (array $overrides = []): array => [
        'national_code' => '1234567890', 'first_name' => 'حسین', 'last_name' => 'راننده',
        'license_type' => $this->licenseType->id, 'status' => 'active', ...$overrides,
    ];
});

test('a branch and its parent may each register the same driver national code, but not twice themselves', function () {
    ($this->as)($this->parentToken)->postJson('/api/user/drivers', ($this->driverPayload)())->assertCreated();
    ($this->as)($this->branchToken)->postJson('/api/user/drivers', ($this->driverPayload)())->assertCreated();

    ($this->as)($this->branchToken)->postJson('/api/user/drivers', ($this->driverPayload)())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['national_code']);
    ($this->as)($this->parentToken)->postJson('/api/user/drivers', ($this->driverPayload)())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['national_code']);
});

test('the parent list shows same named records of every branch with the owner company, a branch only sees its own', function () {
    ($this->as)($this->parentToken)->postJson('/api/user/drivers', ($this->driverPayload)())->assertCreated();
    ($this->as)($this->branchToken)->postJson('/api/user/drivers', ($this->driverPayload)())->assertCreated();

    $rows = collect(($this->as)($this->parentToken)->getJson('/api/user/drivers')->assertSuccessful()->json('data'));

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('owner_company_id')->sort()->values()->all())
        ->toBe([$this->parent->id, $this->branch->id])
        ->and($rows->firstWhere('owner_company_id', $this->branch->id)['owner_company']['name'])->toBe('شعبه تبریز')
        ->and($rows->firstWhere('owner_company_id', $this->parent->id)['owner_company']['name'])->toBe('شرکت سرپرست');

    ($this->as)($this->branchToken)->getJson('/api/user/drivers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.owner_company_id', $this->branch->id)
        ->assertJsonPath('data.0.owner_company.name', 'شعبه تبریز');
});

test('inquiry returns the companys own record first and falls back to a branch record', function () {
    $branchDriver = app(DriverRepositoryInterface::class)->create($this->branch->id, ($this->driverPayload)());

    // سرپرست رکورد خودش را ندارد: رکورد شعبه پیدا می‌شود.
    ($this->as)($this->parentToken)
        ->postJson('/api/user/drivers/inquiry', ['national_code' => '1234567890'])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $branchDriver->id);

    $parentDriver = app(DriverRepositoryInterface::class)->create($this->parent->id, ($this->driverPayload)());

    ($this->as)($this->parentToken)
        ->postJson('/api/user/drivers/inquiry', ['national_code' => '1234567890'])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $parentDriver->id);
});

test('shipment party identifier and fleet smart card and plate are unique per owner company in the database', function () {
    $brand = FleetBrand::query()->create(['name' => 'بنز', 'brand_code' => 10]);
    $type = FleetType::query()->create(['tip_code' => 1001, 'name' => 'اکتروس', 'brand_code' => $brand->brand_code]);
    $loadingType = LoadingType::query()->create(['name' => 'کفی', 'code' => 101]);
    $party = fn () => [
        'national_identifier' => '10101010101', 'is_sender' => true, 'is_receiver' => true,
        'status' => 'active', 'first_name' => 'علی', 'last_name' => 'احمدی',
    ];
    $fleet = fn () => [
        'status' => 'active', 'ownership_type' => 'owned', 'smart_card_number' => 'SC-1',
        'plate_first_number' => '12', 'plate_second_letter' => 'ب', 'plate_third_number' => '345',
        'plate_fourth_number' => '67', 'driver_license_type_id' => $this->licenseType->id,
        'loading_type_id' => $loadingType->id, 'system_id' => $brand->id, 'tip_code' => $type->tip_code,
        'has_violation' => false,
    ];
    $parties = app(ShipmentPartyRepositoryInterface::class);
    $fleets = app(FleetRepositoryInterface::class);

    $parties->create($this->parent->id, $party());
    $parties->create($this->branch->id, $party());
    $fleets->create($this->parent->id, $fleet());
    $fleets->create($this->branch->id, $fleet());

    expect(fn () => $parties->create($this->branch->id, $party()))->toThrow(UniqueConstraintViolationException::class);
    expect(fn () => $fleets->create($this->branch->id, $fleet()))->toThrow(UniqueConstraintViolationException::class);
});

test('syncing an existing table replaces the old global unique indexes with owner scoped ones', function () {
    $partiesTable = "company_{$this->parent->id}_shipment_parties";
    $fleetsTable = "company_{$this->parent->id}_fleets";

    // وضعیت قدیمی: یونیک سراسری روی خود ستون‌ها.
    Schema::table($partiesTable, function ($table) use ($partiesTable): void {
        $table->dropUnique(strtolower("{$partiesTable}_national_identifier_owner_uq"));
        $table->unique('national_identifier', "{$partiesTable}_national_identifier_unique");
    });
    Schema::table($fleetsTable, function ($table) use ($fleetsTable): void {
        $table->dropUnique(strtolower("{$fleetsTable}_smart_card_number_owner_uq"));
        $table->unique('smart_card_number', "{$fleetsTable}_smart_card_number_unique");
        $table->dropUnique("{$fleetsTable}_plate_unique");
        $table->unique(
            ['plate_first_number', 'plate_second_letter', 'plate_third_number', 'plate_fourth_number'],
            "{$fleetsTable}_plate_unique",
        );
    });

    app(CompanyTableService::class)->sync($this->parent->id);

    $indexes = fn (string $table) => collect(Schema::getIndexes($table))->keyBy('name');

    expect($indexes($partiesTable)->has("{$partiesTable}_national_identifier_unique"))->toBeFalse()
        ->and($indexes($partiesTable)[strtolower("{$partiesTable}_national_identifier_owner_uq")]['columns'])
        ->toBe(['owner_company_id', 'national_identifier'])
        ->and($indexes($fleetsTable)->has("{$fleetsTable}_smart_card_number_unique"))->toBeFalse()
        ->and($indexes($fleetsTable)[strtolower("{$fleetsTable}_smart_card_number_owner_uq")]['columns'])
        ->toBe(['owner_company_id', 'smart_card_number'])
        ->and($indexes($fleetsTable)["{$fleetsTable}_plate_unique"]['columns'])
        ->toBe(['owner_company_id', 'plate_first_number', 'plate_second_letter', 'plate_third_number', 'plate_fourth_number']);
});
