<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->withToken($this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);

    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => fake()->unique()->numberBetween(100, 999),
    ]);

    $this->driverPayload = [
        'national_code' => fake()->unique()->numerify('##########'),
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'father_name' => 'رضا',
        'license_number' => fake()->unique()->bothify('LIC-####'),
        'license_type' => $licenseType->id,
        'license_expiry_date' => '2028-08-17',
        'phone_number_1' => '09121234567',
        'status' => StatusEnum::ACTIVE->value,
    ];

    $this->driver = app(DriverRepositoryInterface::class)->create(
        $this->company->id,
        $this->driverPayload,
    );
});

test('driver accounts support crud and always keep one default account', function () {
    $this->getJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk()
        ->assertJsonPath('data.default_account', null);

    $firstAccountId = $this->postJson("/api/user/drivers/{$this->driver->id}/accounts", [
        'sheba_number' => 'IR100000000000000000000001',
        'bank_name' => 'بانک اول',
        'owner_name' => 'علی احمدی',
        'is_default' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_default', true)
        ->json('data.id');

    $secondAccountId = $this->postJson("/api/user/drivers/{$this->driver->id}/accounts", [
        'sheba_number' => 'IR200000000000000000000002',
        'bank_name' => 'بانک دوم',
        'owner_name' => 'علی احمدی',
        'is_default' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_default', false)
        ->json('data.id');

    $this->getJson("/api/user/drivers/{$this->driver->id}/accounts")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/user/drivers/{$this->driver->id}/accounts/{$firstAccountId}")
        ->assertOk()
        ->assertJsonPath('data.bank_name', 'بانک اول');

    $this->getJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk()
        ->assertJsonPath('data.default_account.id', $firstAccountId)
        ->assertJsonPath('data.default_account.sheba_number', 'IR100000000000000000000001');

    $this->patchJson("/api/user/drivers/{$this->driver->id}/accounts/{$secondAccountId}", [
        'bank_name' => 'بانک دوم ویرایش‌شده',
        'is_default' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.bank_name', 'بانک دوم ویرایش‌شده')
        ->assertJsonPath('data.is_default', true);

    $this->getJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk()
        ->assertJsonPath('data.default_account.id', $secondAccountId);

    $this->patchJson("/api/user/drivers/{$this->driver->id}/accounts/{$secondAccountId}", [
        'is_default' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.is_default', false);

    $this->getJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk()
        ->assertJsonPath('data.default_account.id', $firstAccountId);

    $this->deleteJson("/api/user/drivers/{$this->driver->id}/accounts/{$firstAccountId}")
        ->assertOk();

    $this->getJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk()
        ->assertJsonPath('data.default_account.id', $secondAccountId);

    $this->deleteJson("/api/user/drivers/{$this->driver->id}")
        ->assertOk();

    $this->assertDatabaseMissing("company_{$this->company->id}_driver_accounts", [
        'driver_id' => $this->driver->id,
    ]);
});

test('driver account endpoints validate data and scope accounts to their driver', function () {
    $this->postJson("/api/user/drivers/{$this->driver->id}/accounts", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sheba_number', 'bank_name', 'owner_name']);

    $accountId = $this->postJson("/api/user/drivers/{$this->driver->id}/accounts", [
        'sheba_number' => 'IR300000000000000000000003',
        'bank_name' => 'بانک تست',
        'owner_name' => 'علی احمدی',
    ])->assertCreated()->json('data.id');

    $otherDriver = app(DriverRepositoryInterface::class)->create(
        $this->company->id,
        [
            ...$this->driverPayload,
            'national_code' => fake()->unique()->numerify('##########'),
            'license_number' => fake()->unique()->bothify('LIC-####'),
        ],
    );

    $this->getJson("/api/user/drivers/{$otherDriver->id}/accounts/{$accountId}")
        ->assertNotFound();
    $this->patchJson("/api/user/drivers/{$otherDriver->id}/accounts/{$accountId}", [
        'bank_name' => 'نام اشتباه',
    ])->assertNotFound();
    $this->deleteJson("/api/user/drivers/{$otherDriver->id}/accounts/{$accountId}")
        ->assertNotFound();

    $this->postJson('/api/user/drivers/999999/accounts', [
        'sheba_number' => 'IR400000000000000000000004',
        'bank_name' => 'بانک تست',
        'owner_name' => 'علی احمدی',
    ])->assertNotFound();
});
