<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\GeneralOptionRepositoryInterface;
use App\Models\Cargo;
use App\Models\City;
use App\Models\Company;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\InsuranceCompany;
use App\Models\Packaging;
use App\Models\State;
use App\Models\User;
use App\Repositories\General\GeneralOptionRepository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $this->withToken($user->createToken(
        'general-options',
        ['company-support', "company:{$company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('it binds the general option repository interface', function () {
    expect(app(GeneralOptionRepositoryInterface::class))
        ->toBeInstanceOf(GeneralOptionRepository::class);
});

test('it returns searchable cargo packaging fleet type and fleet system options', function () {
    $cargo = Cargo::query()->create(['name' => 'گندم ویژه', 'code' => 1001]);
    $packaging = Packaging::query()->create(['unit_name' => 'کیسه ویژه', 'code' => 2001]);
    $system = FleetBrand::query()->create(['name' => 'سیستم تست', 'brand_code' => 3001]);
    $fleetType = FleetType::query()->create([
        'tip_code' => 4001,
        'name' => 'تیپ تست',
        'brand_code' => $system->brand_code,
    ]);

    $this->getJson('/api/user/general/cargos?search=گندم&paginate=1&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $cargo->id);

    $this->getJson('/api/user/general/packaging?search=کیسه&paginate=1&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $packaging->id);

    $this->getJson('/api/user/general/fleet-systems?search=سیستم&paginate=1&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.brand_code', $system->brand_code);

    $this->getJson("/api/user/general/fleet-types?eq-brand_code={$system->brand_code}&paginate=1&itemsPerPage=10")
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.tip_code', $fleetType->tip_code)
        ->assertJsonPath('data.data.0.brand_code', $system->brand_code);
});

test('it filters cities by state and only returns active insurance companies', function () {
    $firstState = State::query()->create(['name' => 'استان اول', 'code' => 51]);
    $secondState = State::query()->create(['name' => 'استان دوم', 'code' => 52]);
    $city = City::query()->create([
        'name' => 'شهر اول',
        'code' => 5101,
        'state_id' => $firstState->id,
    ]);
    City::query()->create([
        'name' => 'شهر دوم',
        'code' => 5201,
        'state_id' => $secondState->id,
    ]);

    $activeInsuranceCompany = InsuranceCompany::query()->forceCreate([
        'name' => 'بیمه فعال',
        'org_code' => 6001,
        'status' => StatusEnum::ACTIVE->value,
    ]);
    InsuranceCompany::query()->forceCreate([
        'name' => 'بیمه غیرفعال',
        'org_code' => 6002,
        'status' => StatusEnum::INACTIVE->value,
    ]);

    $this->getJson('/api/user/general/states?search=اول&paginate=1&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $firstState->id);

    $this->getJson("/api/user/general/cities?eq-state_id={$firstState->id}&paginate=1&itemsPerPage=10")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $city->id);

    $this->getJson('/api/user/general/insurance-companies?paginate=1&itemsPerPage=10')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $activeInsuranceCompany->id);
});
