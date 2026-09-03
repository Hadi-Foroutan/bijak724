<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->parentCompany = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '71001',
        'organization_code' => 'ORG-71001',
        'name' => 'شرکت اصلی',
        'national_code' => '71000000001',
        'city_code' => 1101,
    ]);
    $this->branchCompany = Company::query()->forceCreate([
        'parent_id' => $this->parentCompany->id,
        'parent_type' => 'branch',
        'panel_code' => '71002',
        'organization_code' => 'ORG-71002',
        'name' => 'شعبه شرکت',
        'national_code' => '71000000002',
        'city_code' => 1101,
    ]);

    $this->actor = User::query()->forceCreate([
        'company_id' => $this->branchCompany->id,
        'national_code' => '7100000000',
        'first_name' => 'کاربر',
        'last_name' => 'شعبه',
        'phone' => '09127100000',
        'username' => 'branch-user',
        'password' => 'password',
    ]);

    $token = $this->actor->createToken(
        'branch-session',
        ['company-support', "company:{$this->branchCompany->id}"],
        now()->addMinutes(30),
    )->plainTextToken;

    $this->withToken($token);
});

test('branches share parent tables but only access records they own', function () {
    foreach (array_keys(config('company_tables')) as $tableKey) {
        expect(Schema::hasTable("company_{$this->parentCompany->id}_{$tableKey}"))->toBeTrue()
            ->and(Schema::hasColumn(
                "company_{$this->parentCompany->id}_{$tableKey}",
                'owner_company_id',
            ))->toBeTrue()
            ->and(Schema::hasTable("company_{$this->branchCompany->id}_{$tableKey}"))->toBeFalse();
    }

    $repository = app(CompanyDataRepositoryInterface::class);
    $parentCargo = $repository->create($this->parentCompany->id, 'cargos', [
        'name' => 'بار شرکت اصلی',
        'national_code' => 'PARENT-CARGO',
    ]);

    expect($repository->table($this->branchCompany->id, 'cargos'))
        ->toBe("company_{$this->parentCompany->id}_cargos");

    $this->getJson('/api/user/cargos')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->getJson("/api/user/cargos/{$parentCargo->id}")
        ->assertNotFound();

    $branchCargoId = $this->postJson('/api/user/cargos', [
        'name' => 'بار ثبت‌شده توسط شعبه',
        'national_code' => 'BRANCH-CARGO',
    ])
        ->assertCreated()
        ->json('data.id');

    $this->assertDatabaseHas("company_{$this->parentCompany->id}_cargos", [
        'id' => $branchCargoId,
        'owner_company_id' => $this->branchCompany->id,
        'name' => 'بار ثبت‌شده توسط شعبه',
    ]);

    $otherBranch = Company::query()->forceCreate([
        'parent_id' => $this->parentCompany->id,
        'parent_type' => 'branch',
        'panel_code' => '71003',
        'organization_code' => 'ORG-71003',
        'name' => 'شعبه دیگر',
        'national_code' => '71000000003',
        'city_code' => 1101,
    ]);
    $repository->create($otherBranch->id, 'cargos', [
        'name' => 'بار شعبه دیگر',
        'national_code' => 'OTHER-BRANCH-CARGO',
    ]);

    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);
    $parentDriver = $repository->create($this->parentCompany->id, 'drivers', [
        'national_code' => '7100000099',
        'first_name' => 'راننده',
        'last_name' => 'شرکت اصلی',
        'full_name' => 'راننده شرکت اصلی',
        'father_name' => 'پدر راننده',
        'license_number' => 'PARENT-LICENSE',
        'license_type' => $licenseType->id,
        'license_expiry_date' => '2030-01-01',
    ]);

    $this->postJson('/api/user/waybills', [
        'tracking_code' => 'FORBIDDEN-PARENT-DRIVER',
        'driver1_id' => $parentDriver->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('driver1_id');

    expect($repository->query($this->branchCompany->id, 'cargos')->pluck('id')->all())
        ->toBe([$branchCargoId])
        ->and($repository->query($this->parentCompany->id, 'cargos')->count())->toBe(3);
});
