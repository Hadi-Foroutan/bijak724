<?php

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Cargo;
use App\Models\Company;
use App\Models\Company\Driver as CompanyDriver;
use App\Models\DriverLicenseType;
use App\Models\User;
use App\Services\Company\CompanyDataOwnerResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-09-23 12:00:00');
    config([
        'cache.default' => 'array',
        'dashboard.cache_ttl_seconds' => 7200,
    ]);
    Cache::clear();

    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->driverLicenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $this->withToken($this->user->createToken(
        'dashboard-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('it returns company dashboard summary', function () {
    $waybillTable = "company_{$this->company->id}_waybills";

    expect(Schema::hasIndex($waybillTable, "{$waybillTable}_dashboard_issued_index"))->toBeTrue()
        ->and(Schema::hasIndex($waybillTable, "{$waybillTable}_dashboard_created_index"))->toBeTrue();

    insertDashboardWaybill($this->company, [
        'created_at' => '2026-09-23 08:00:00',
        'updated_at' => '2026-09-23 08:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'created_at' => '2026-09-23 09:00:00',
        'updated_at' => '2026-09-23 09:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'created_at' => '2026-09-22 09:00:00',
        'updated_at' => '2026-09-22 09:00:00',
    ]);

    createDashboardDriver($this, 1);
    createDashboardDriver($this, 2);
    insertDashboardFleet($this->company);

    $this->getJson('/api/user/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.total_waybills', 3)
        ->assertJsonPath('data.today_waybills', 2)
        ->assertJsonPath('data.total_drivers', 2)
        ->assertJsonPath('data.total_fleets', 1);
});

test('it scopes dashboard data and cache to a branch company', function () {
    $branch = Company::factory()->create([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
    ]);

    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-23 08:00:00',
    ]);
    insertDashboardWaybill($branch, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-23 09:00:00',
    ]);

    $this->withToken($this->user->createToken(
        'branch-dashboard-session',
        ['company-support', "company:{$branch->id}"],
        now()->addMinutes(30),
    )->plainTextToken);

    $this->getJson('/api/user/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.total_waybills', 1)
        ->assertJsonPath('data.today_waybills', 1);

    $this->getJson('/api/user/dashboard/waybills/daily')
        ->assertSuccessful()
        ->assertJsonPath('data.items.29.count', 1);
});

test('it returns and independently caches the issued waybill daily series', function () {
    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-23 08:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-23 09:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-22 09:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'is_incomplete' => true,
        'issued_at' => '2026-09-23 10:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-08-24 10:00:00',
    ]);

    $response = $this->getJson('/api/user/dashboard/waybills/daily')
        ->assertSuccessful()
        ->assertJsonPath('data.from', '2026-08-25')
        ->assertJsonPath('data.to', '2026-09-23')
        ->assertJsonCount(30, 'data.items');

    $items = collect($response->json('data.items'))->keyBy('date');

    expect($items->get('2026-08-25')['count'])->toBe(0)
        ->and($items->get('2026-09-22')['count'])->toBe(1)
        ->and($items->get('2026-09-23')['count'])->toBe(2);

    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-09-23 11:00:00',
    ]);

    $this->getJson('/api/user/dashboard/waybills/daily')
        ->assertSuccessful()
        ->assertJsonPath('data.items.29.count', 2);

    $this->getJson('/api/user/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.total_waybills', 6);
});

test('it returns issued waybill totals for the current and previous five months', function () {
    foreach (['2026-04-01 08:00:00', '2026-04-30 23:59:59', '2026-09-23 08:00:00'] as $issuedAt) {
        insertDashboardWaybill($this->company, [
            'is_incomplete' => false,
            'issued_at' => $issuedAt,
        ]);
    }

    insertDashboardWaybill($this->company, [
        'is_incomplete' => true,
        'issued_at' => '2026-09-23 09:00:00',
    ]);
    insertDashboardWaybill($this->company, [
        'is_incomplete' => false,
        'issued_at' => '2026-03-31 23:59:59',
    ]);

    $response = $this->getJson('/api/user/dashboard/waybills/monthly')
        ->assertSuccessful()
        ->assertJsonPath('data.from', '2026-04')
        ->assertJsonPath('data.to', '2026-09')
        ->assertJsonCount(6, 'data.items');

    $items = collect($response->json('data.items'))->keyBy('month');

    expect($items->keys()->all())->toBe([
        '2026-04',
        '2026-05',
        '2026-06',
        '2026-07',
        '2026-08',
        '2026-09',
    ])->and($items->get('2026-04')['count'])->toBe(2)
        ->and($items->get('2026-05')['count'])->toBe(0)
        ->and($items->get('2026-09')['count'])->toBe(1);
});

test('it returns the five cargos used in the most issued waybills', function () {
    $cargos = collect(range(1, 6))->map(fn (int $number): Cargo => Cargo::query()->create([
        'name' => "محموله {$number}",
        'code' => 1200000 + $number,
    ]));

    foreach ($cargos as $index => $cargo) {
        foreach (range(1, 6 - $index) as $occurrence) {
            $waybillId = insertDashboardWaybill($this->company, [
                'is_incomplete' => false,
                'issued_at' => "2026-09-{$occurrence} 08:00:00",
            ]);

            insertDashboardWaybillCargo($this->company, $waybillId, $cargo->id);

            if ($index === 0 && $occurrence === 1) {
                insertDashboardWaybillCargo($this->company, $waybillId, $cargo->id);
            }
        }
    }

    $this->getJson('/api/user/dashboard/cargos/top')
        ->assertSuccessful()
        ->assertJsonCount(5, 'data.items')
        ->assertJsonPath('data.items.0.cargo_id', $cargos[0]->id)
        ->assertJsonPath('data.items.0.cargo_code', $cargos[0]->code)
        ->assertJsonPath('data.items.0.cargo_name', 'محموله 1')
        ->assertJsonPath('data.items.0.count', 6)
        ->assertJsonPath('data.items.4.cargo_id', $cargos[4]->id)
        ->assertJsonPath('data.items.4.count', 2);
});

test('it returns the ten primary drivers with the most issued waybills', function () {
    $drivers = collect(range(1, 11))->map(
        fn (int $number) => createDashboardDriver($this, $number),
    );

    foreach ($drivers as $driver) {
        insertDashboardWaybill($this->company, [
            'driver1_id' => $driver->id,
            'is_incomplete' => false,
            'issued_at' => '2026-09-01 08:00:00',
        ]);
    }

    foreach (range(1, 2) as $occurrence) {
        insertDashboardWaybill($this->company, [
            'driver1_id' => $drivers[10]->id,
            'is_incomplete' => false,
            'issued_at' => "2026-09-0{$occurrence} 09:00:00",
        ]);
    }

    $response = $this->getJson('/api/user/dashboard/drivers/top')
        ->assertSuccessful()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('data.items.0.driver_id', $drivers[10]->id)
        ->assertJsonPath('data.items.0.full_name', 'راننده 11')
        ->assertJsonPath('data.items.0.count', 3);

    expect(collect($response->json('data.items'))->pluck('driver_id'))
        ->not->toContain($drivers[9]->id);
});

function insertDashboardWaybill(Company $company, array $attributes = []): int
{
    $dataOwnerCompanyId = app(CompanyDataOwnerResolver::class)->resolveId($company->id);

    return DB::table("company_{$dataOwnerCompanyId}_waybills")->insertGetId([
        'owner_company_id' => $company->id,
        'is_incomplete' => true,
        'freight_at_origin' => false,
        'is_fixed' => false,
        'created_at' => '2026-09-23 12:00:00',
        'updated_at' => '2026-09-23 12:00:00',
        ...$attributes,
    ]);
}

function insertDashboardWaybillCargo(Company $company, int $waybillId, int $cargoId): void
{
    DB::table("company_{$company->id}_waybill_cargos")->insert([
        'owner_company_id' => $company->id,
        'waybill_id' => $waybillId,
        'cargo_id' => $cargoId,
        'created_at' => '2026-09-23 12:00:00',
        'updated_at' => '2026-09-23 12:00:00',
    ]);
}

function createDashboardDriver(object $test, int $number): CompanyDriver
{
    return app(DriverRepositoryInterface::class)->create($test->company->id, [
        'national_code' => str_pad((string) $number, 10, '0', STR_PAD_LEFT),
        'first_name' => 'راننده',
        'last_name' => (string) $number,
        'father_name' => 'پدر',
        'license_number' => "LICENSE-{$number}",
        'license_type' => $test->driverLicenseType->id,
        'license_expiry_date' => '2030-01-01',
        'status' => StatusEnum::ACTIVE->value,
    ]);
}

function insertDashboardFleet(Company $company): void
{
    DB::table("company_{$company->id}_fleets")->insert([
        'owner_company_id' => $company->id,
        'status' => StatusEnum::ACTIVE->value,
        'ownership_type' => FleetOwnershipType::Owned->value,
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'has_violation' => false,
        'created_at' => '2026-09-23 12:00:00',
        'updated_at' => '2026-09-23 12:00:00',
    ]);
}
