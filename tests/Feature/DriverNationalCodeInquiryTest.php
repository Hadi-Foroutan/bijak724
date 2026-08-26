<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Company;
use App\Models\DriverLicenseType;
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
        'name' => 'شرکت تست استعلام راننده',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $token = $this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;

    $this->withToken($token);

    $this->driverLicenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);
});

test('it returns a company driver by national code using the driver resource', function () {
    $driver = app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'drivers',
        driverInquiryPayload($this->driverLicenseType->id),
    );

    $this->getJson('/api/user/drivers/inquiry/1234567891')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $driver->id)
        ->assertJsonPath('data.national_code', '1234567891')
        ->assertJsonPath('data.first_name', 'علی')
        ->assertJsonPath('data.license_number', 'LIC-1001')
        ->assertJsonMissingPath('data.table');
});

test('it validates the national code used for driver inquiry', function () {
    $this->getJson('/api/user/drivers/inquiry/1234')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('national_code');
});

test('it does not return a driver from another company', function () {
    $otherCompany = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت دیگر',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    app(CompanyDataRepositoryInterface::class)->create(
        $otherCompany->id,
        'drivers',
        driverInquiryPayload($this->driverLicenseType->id),
    );

    $this->getJson('/api/user/drivers/inquiry/1234567891')
        ->assertNotFound();
});

function driverInquiryPayload(int $licenseTypeId): array
{
    return [
        'national_code' => '1234567891',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'father_name' => 'رضا',
        'license_number' => 'LIC-1001',
        'license_type' => $licenseTypeId,
        'license_expiry_date' => '2028-08-17',
        'phone_number_1' => '09121234567',
        'phone_number_2' => null,
        'phone_number_3' => null,
        'description' => 'راننده تست',
        'status' => StatusEnum::ACTIVE->value,
    ];
}
