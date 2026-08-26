<?php

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\City;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('advanced search works for regular eloquent models', function () {
    $tehran = State::query()->create(['name' => 'تهران', 'code' => 11]);
    $fars = State::query()->create(['name' => 'فارس', 'code' => 41]);

    City::query()->create([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $tehran->id,
    ]);
    $shiraz = City::query()->create([
        'name' => 'شیراز',
        'code' => 1401,
        'state_id' => $fars->id,
    ]);
    City::query()->create([
        'name' => 'اصفهان',
        'code' => 2101,
        'state_id' => $tehran->id,
    ]);

    $cities = City::searchRecords([
        'min-code' => 1200,
        'max-code' => 2000,
        'state__name' => 'فارس',
        'order_field' => 'code',
        'order_type' => 'ASC',
    ]);

    expect($cities)->toHaveCount(1)
        ->and($cities->first()->is($shiraz))->toBeTrue();
});

test('advanced search paginates regular models and supports global search', function () {
    $state = State::query()->create(['name' => 'تهران', 'code' => 11]);

    City::query()->create(['name' => 'شهریار', 'code' => 1102, 'state_id' => $state->id]);
    City::query()->create(['name' => 'تهران', 'code' => 1101, 'state_id' => $state->id]);

    $cities = City::searchRecords([
        'search' => 'تهران',
        'paginate' => 'true',
        'itemsPerPage' => 1,
    ]);

    expect($cities)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($cities->total())->toBe(1)
        ->and($cities->perPage())->toBe(1);
});

test('the same advanced search rules work on company dynamic tables', function () {
    $company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '10001',
        'organization_code' => 'ORG-SEARCH-1',
        'name' => 'شرکت جستجو',
        'national_code' => '10000000001',
        'city_code' => '1101',
    ]);

    $repository = app(CompanyDataRepositoryInterface::class);
    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $repository->create($company->id, 'drivers', dynamicDriverPayload(
        nationalCode: '1234567891',
        firstName: 'علی',
        lastName: 'احمدی',
        status: 'active',
        licenseTypeId: $licenseType->id,
    ));
    $repository->create($company->id, 'drivers', dynamicDriverPayload(
        nationalCode: '1234567892',
        firstName: 'رضا',
        lastName: 'محمدی',
        status: 'inactive',
        licenseTypeId: $licenseType->id,
    ));

    $drivers = $repository->search($company->id, 'drivers', [
        'search' => 'احمدی',
        'eq-status' => 'active',
        'notEq-national_code' => '1234567892',
        'order_field' => 'national_code',
        'order_type' => 'DESC',
        'paginate' => true,
        'itemsPerPage' => 1,
    ]);

    expect($drivers)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($drivers->total())->toBe(1)
        ->and($drivers->items()[0]->national_code)->toBe('1234567891')
        ->and($drivers->items()[0]->getTable())->toBe("company_{$company->id}_drivers");
});

function dynamicDriverPayload(
    string $nationalCode,
    string $firstName,
    string $lastName,
    string $status,
    int $licenseTypeId,
): array {
    return [
        'national_code' => $nationalCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'father_name' => 'حسن',
        'license_number' => "LIC-{$nationalCode}",
        'license_type' => $licenseTypeId,
        'license_expiry_date' => '2028-01-01',
        'phone_number_1' => '09121234567',
        'phone_number_2' => null,
        'phone_number_3' => null,
        'description' => null,
        'status' => $status,
    ];
}
