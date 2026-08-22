<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Company;
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
        'name' => 'شرکت تست رانندگان',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $companyToken = $this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($companyToken);

    $this->driverPayload = [
        'national_code' => '1234567891',
        'name' => 'علی',
        'last_name' => 'احمدی',
        'father_name' => 'رضا',
        'license_number' => 'LIC-1001',
        'license_type' => 'پایه یک',
        'license_expiry_date' => '2028-08-17',
        'phone_number_1' => '09121234567',
        'phone_number_2' => '02112345678',
        'phone_number_3' => null,
        'description' => 'راننده شیفت صبح',
        'status' => StatusEnum::ACTIVE->value,
    ];
});

test('it creates shows and lists drivers in the company table', function () {
    $response = $this->postJson(
        '/api/user/drivers',
        $this->driverPayload,
    )
        ->assertCreated()
        ->assertJsonPath('data.national_code', '1234567891')
        ->assertJsonPath('data.status', StatusEnum::ACTIVE->value);

    $driverId = $response->json('data.id');
    $driverTable = "company_{$this->company->id}_drivers";

    $this->assertDatabaseHas($driverTable, [
        'id' => $driverId,
        'license_number' => 'LIC-1001',
    ]);

    $this->getJson("/api/user/drivers/{$driverId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_name', 'احمدی');

    $this->getJson('/api/user/drivers?search=احمدی')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $driverId);
});

test('it updates and deletes a driver', function () {
    $driver = app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'drivers',
        $this->driverPayload,
    );

    $this->patchJson("/api/user/drivers/{$driver->id}", [
        'last_name' => 'محمدی',
        'status' => StatusEnum::INACTIVE->value,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.last_name', 'محمدی')
        ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

    $this->deleteJson("/api/user/drivers/{$driver->id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing("company_{$this->company->id}_drivers", [
        'id' => $driver->id,
    ]);
});

test('it validates driver data and company scoped national code uniqueness', function () {
    app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'drivers',
        $this->driverPayload,
    );

    $this->postJson('/api/user/drivers', [
        ...$this->driverPayload,
        'license_expiry_date' => '17/08/2028',
        'status' => 'blocked',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'national_code',
            'license_expiry_date',
            'status',
        ]);
});

test('a driver cannot be accessed through another company', function () {
    $driver = app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'drivers',
        $this->driverPayload,
    );

    $otherCompany = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت دیگر',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $otherCompanyToken = $this->user->createToken(
        'other-company-session',
        ['company-support', "company:{$otherCompany->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    app('auth')->forgetGuards();

    $this->withToken($otherCompanyToken)
        ->getJson("/api/user/drivers/{$driver->id}")
        ->assertNotFound();
});
