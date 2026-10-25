<?php

use App\Enums\WaybillStatus;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\Insurance;
use App\Models\LoadingType;
use App\Models\TechnicalManager;
use App\Models\User;
use App\Services\Company\TransportContract\DefaultTransportContractService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->withToken($this->user->createToken('scoped', ["company:{$this->company->id}"])->plainTextToken);
});

test('references to another companys records are rejected by the company scoped exists rule', function () {
    $other = $this->otherCompany->id;

    $licenseType = DriverLicenseType::query()->create(['name' => 'پایه یک', 'code' => 1]);
    $loadingType = LoadingType::query()->create(['name' => 'کفی', 'code' => 101]);
    $brand = FleetBrand::query()->create(['name' => 'بنز', 'brand_code' => 10]);
    $type = FleetType::query()->create(['tip_code' => 1001, 'name' => 'اکتروس', 'brand_code' => $brand->brand_code]);

    $foreignParty = app(ShipmentPartyRepositoryInterface::class)->create($other, [
        'national_identifier' => '10101010101', 'is_sender' => true, 'is_receiver' => true,
        'status' => 'active', 'first_name' => 'علی', 'last_name' => 'دیگر',
    ]);
    $foreignDriver = app(DriverRepositoryInterface::class)->create($other, [
        'national_code' => '1234567890', 'first_name' => 'حسین', 'last_name' => 'دیگر',
        'license_type' => $licenseType->id, 'status' => 'active',
    ]);
    $foreignFleet = app(FleetRepositoryInterface::class)->create($other, [
        'status' => 'active', 'ownership_type' => 'owned', 'plate_first_number' => '12',
        'plate_second_letter' => 'ب', 'plate_third_number' => '345', 'plate_fourth_number' => '67',
        'driver_license_type_id' => $licenseType->id, 'loading_type_id' => $loadingType->id,
        'system_id' => $brand->id, 'tip_code' => $type->tip_code, 'has_violation' => false,
    ]);
    $foreignOwner = app(ProductOwnerRepositoryInterface::class)->create($other, [
        'name' => 'صاحب کالای دیگر', 'phone' => '09120000003',
    ]);
    $foreignInsurance = Insurance::factory()->for($this->otherCompany)->create();
    $foreignManager = TechnicalManager::factory()->for($this->otherCompany)->create();
    app(DefaultTransportContractService::class)->createForCompany($this->otherCompany);
    $foreignContract = $this->otherCompany->transportContracts()->firstOrFail();

    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'sender_id' => $foreignParty->id,
        'receiver_id' => $foreignParty->id,
        'driver1_id' => $foreignDriver->id,
        'driver2_id' => $foreignDriver->id,
        'referral_driver_id' => $foreignDriver->id,
        'fleet_id' => $foreignFleet->id,
        'liability_insurance' => $foreignInsurance->id,
        'technical_manager_id' => $foreignManager->id,
        'transport_contract_id' => $foreignContract->id,
        'cargos' => [['origin_weight' => 10, 'product_owner_id' => $foreignOwner->id]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id', 'receiver_id', 'driver1_id', 'driver2_id', 'referral_driver_id', 'fleet_id',
            'liability_insurance', 'technical_manager_id', 'transport_contract_id', 'cargos.0.product_owner_id',
        ]);
});

test('the company scoped unique rules only look at the current company table', function () {
    $licenseType = DriverLicenseType::query()->create(['name' => 'پایه یک', 'code' => 1]);
    $payload = [
        'national_code' => '1234567890', 'first_name' => 'حسین', 'last_name' => 'راننده',
        'license_type' => $licenseType->id, 'status' => 'active',
    ];

    // کد ملی راننده فقط داخل شرکت خودش یکتاست؛ همان کد در شرکت دیگر مجاز است.
    app(DriverRepositoryInterface::class)->create($this->otherCompany->id, $payload);
    $driverId = $this->postJson('/api/user/drivers', $payload)->assertCreated()->json('data.id');

    $this->postJson('/api/user/drivers', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['national_code']);

    // ویرایش خودش با همان کد ملی مجاز است.
    $this->postJson("/api/user/drivers/{$driverId}", ['national_code' => '1234567890'])->assertSuccessful();
});
