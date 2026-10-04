<?php

use App\Enums\TransportContractItemName;
use App\Enums\WaybillStatus;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Cargo;
use App\Models\City;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\Insurance;
use App\Models\LoadingType;
use App\Models\Packaging;
use App\Models\State;
use App\Models\User;
use App\Services\Company\Waybill\WaybillService;
use App\Services\Company\Waybill\WaybillTrackingCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create([
        'print_name' => 'کاربر صادرکننده',
    ]);
    $this->company = Company::factory()->create();
    $this->insurance = Insurance::factory()->for($this->company)->create();
    $this->withToken($this->user->createToken(
        'waybill-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);

    $this->cargo = Cargo::query()->create(['name' => 'گندم', 'code' => 1200000]);
    $this->packaging = Packaging::query()->create(['unit_name' => 'کیسه', 'code' => 8]);
    $this->contract = $this->company->transportContracts()->with('items')->sole();
    $this->contract->items->firstWhere('name', TransportContractItemName::WeighbridgeCost)
        ->update(['primary_value' => 5]);
    $this->contract->items->firstWhere('name', TransportContractItemName::InsurancePremium)
        ->update(['primary_value' => 2]);

    $productOwnerRepository = app(ProductOwnerRepositoryInterface::class);
    $shipmentPartyRepository = app(ShipmentPartyRepositoryInterface::class);
    $addressRepository = app(ShipmentPartyAddressRepositoryInterface::class);
    $driverRepository = app(DriverRepositoryInterface::class);
    $fleetRepository = app(FleetRepositoryInterface::class);

    $this->productOwner = $productOwnerRepository->create($this->company->id, [
        'name' => 'صاحب کالا',
        'phone' => '09120000003',
    ]);
    $this->sender = $shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '10101010101',
        'is_sender' => true,
        'is_receiver' => false,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'فرستنده',
        'mobile' => '09120000001',
    ]);
    $this->receiver = $shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '20202020202',
        'is_sender' => false,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'رضا',
        'last_name' => 'گیرنده',
        'mobile' => '09120000002',
    ]);
    $state = State::query()->forceCreate(['name' => 'تهران', 'code' => 11]);
    $city = City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);
    $this->senderAddress = $addressRepository->create($this->company->id, [
        'shipment_party_id' => $this->sender->id,
        'postal_code' => '1111111111',
        'city_code' => $city->code,
        'address' => 'تهران، آدرس فرستنده',
    ]);
    $this->receiverAddress = $addressRepository->create($this->company->id, [
        'shipment_party_id' => $this->receiver->id,
        'postal_code' => '2222222222',
        'city_code' => $city->code,
        'address' => 'تهران، آدرس گیرنده',
    ]);

    $licenseType = DriverLicenseType::query()->create(['name' => 'پایه یک', 'code' => 1]);
    $loadingType = LoadingType::query()->create(['name' => 'کفی', 'code' => 101]);
    $fleetBrand = FleetBrand::query()->create(['name' => 'بنز', 'brand_code' => 10]);
    $fleetType = FleetType::query()->create([
        'tip_code' => 1001,
        'name' => 'اکتروس',
        'brand_code' => $fleetBrand->brand_code,
    ]);
    $driverData = [
        'father_name' => 'حسن',
        'license_type' => $licenseType->id,
        'license_expiry_date' => '2030-01-01',
        'status' => 'active',
    ];
    $this->firstDriver = $driverRepository->create($this->company->id, [
        ...$driverData,
        'national_code' => '1234567890',
        'first_name' => 'حسین',
        'last_name' => 'راننده',
        'license_number' => 'LIC-1',
        'phone_number_1' => '09121111111',
    ]);
    $this->secondDriver = $driverRepository->create($this->company->id, [
        ...$driverData,
        'national_code' => '0987654321',
        'first_name' => 'محمد',
        'last_name' => 'کمک راننده',
        'license_number' => 'LIC-2',
        'phone_number_1' => '09122222222',
    ]);
    $this->thirdDriver = $driverRepository->create($this->company->id, [
        ...$driverData,
        'national_code' => '1122334455',
        'first_name' => 'عباس',
        'last_name' => 'راننده حواله',
        'license_number' => 'LIC-3',
        'phone_number_1' => '09123333333',
    ]);
    $this->fleet = $fleetRepository->create($this->company->id, [
        'status' => 'active',
        'ownership_type' => 'owned',
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'driver_license_type_id' => $licenseType->id,
        'loading_type_id' => $loadingType->id,
        'system_id' => $fleetBrand->id,
        'tip_code' => $fleetType->tip_code,
        'has_violation' => false,
    ]);
    $defaultBijakId = $this->getJson('/api/user/bijak-numbers')
        ->assertSuccessful()
        ->json('data.0.id');
    $this->patchJson("/api/user/bijak-numbers/{$defaultBijakId}", [
        'status' => 'inactive',
    ])->assertSuccessful();
    $this->bijakNumberId = $this->postJson('/api/user/bijak-numbers', [
        'title' => 'دفتر حواله',
        'serial_number' => 'SERIAL-1',
        'from_number' => 1,
        'to_number' => 2000,
    ])->assertCreated()->json('data.id');

    $defaultReferralId = $this->getJson('/api/user/referral-numbers')
        ->assertSuccessful()
        ->json('data.0.id');
    $this->patchJson("/api/user/referral-numbers/{$defaultReferralId}", [
        'status' => 'inactive',
    ])->assertSuccessful();
    $this->referralNumberId = $this->postJson('/api/user/referral-numbers', [
        'title' => 'دفتر حواله',
        'serial_number' => 'SERIAL-1',
        'from_number' => 1,
        'to_number' => 2000,
    ])->assertCreated()->json('data.id');
});

test('it exposes origin and descination address relations', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'sender_id' => $this->sender->id,
        'sender_address_id' => $this->senderAddress->id,
        'receiver_id' => $this->receiver->id,
        'receiver_address_id' => $this->receiverAddress->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.origin.id', $this->senderAddress->id)
        ->assertJsonPath('data.origin.postal_code', '1111111111')
        ->assertJsonPath('data.descination.id', $this->receiverAddress->id)
        ->assertJsonPath('data.descination.postal_code', '2222222222')
        ->json('data.id');

    $this->senderAddress->update(['postal_code' => '3333333333']);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.origin.id', $this->senderAddress->id)
        ->assertJsonPath('data.origin.postal_code', '3333333333')
        ->assertJsonPath('data.descination.id', $this->receiverAddress->id)
        ->assertJsonPath('data.descination.postal_code', '2222222222');
});

test('it stores reference snapshot names only as full names', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'driver1_id' => $this->firstDriver->id,
        'driver2_id' => $this->secondDriver->id,
        'referral_driver_id' => $this->thirdDriver->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.sender_full_name', 'علی فرستنده')
        ->assertJsonPath('data.receiver_full_name', 'رضا گیرنده')
        ->assertJsonPath('data.driver1_full_name', 'حسین راننده')
        ->assertJsonPath('data.driver2_full_name', 'محمد کمک راننده')
        ->assertJsonPath('data.referral_driver_full_name', 'عباس راننده حواله')
        ->assertJsonMissingPath('data.sender_first_name')
        ->assertJsonMissingPath('data.sender_last_name')
        ->assertJsonMissingPath('data.receiver_first_name')
        ->assertJsonMissingPath('data.receiver_last_name')
        ->assertJsonMissingPath('data.driver1_first_name')
        ->assertJsonMissingPath('data.driver1_last_name')
        ->assertJsonMissingPath('data.driver2_first_name')
        ->assertJsonMissingPath('data.driver2_last_name')
        ->assertJsonMissingPath('data.referral_driver_first_name')
        ->assertJsonMissingPath('data.referral_driver_last_name')
        ->json('data.id');

    $waybillTable = "company_{$this->company->id}_waybills";

    $this->assertDatabaseHas($waybillTable, [
        'id' => $waybillId,
        'sender_full_name' => 'علی فرستنده',
        'receiver_full_name' => 'رضا گیرنده',
        'driver1_full_name' => 'حسین راننده',
        'driver2_full_name' => 'محمد کمک راننده',
        'referral_driver_full_name' => 'عباس راننده حواله',
    ]);

    expect(Schema::hasColumn($waybillTable, 'sender_first_name'))->toBeFalse()
        ->and(Schema::hasColumn($waybillTable, 'driver1_last_name'))->toBeFalse()
        ->and(Schema::hasColumn($waybillTable, 'referral_driver_first_name'))->toBeFalse();

    $this->sender->update(['first_name' => 'نام جدید']);
    $this->firstDriver->update(['first_name' => 'راننده جدید']);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.sender_full_name', 'علی فرستنده')
        ->assertJsonPath('data.driver1_full_name', 'حسین راننده');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'driver1_id' => $this->firstDriver->id,
        'driver2_id' => $this->secondDriver->id,
        'referral_driver_id' => $this->thirdDriver->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.sender_full_name', 'علی فرستنده')
        ->assertJsonPath('data.driver1_full_name', 'حسین راننده')
        ->assertJsonMissingPath('data.sender_first_name')
        ->assertJsonMissingPath('data.driver1_last_name');
});

test('it creates a complete waybill with snapshots cargos and calculated contract amounts', function () {
    $response = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.sender_address_id', $this->senderAddress->id)
        ->assertJsonPath('data.sender_address.postal_code', '1111111111')
        ->assertJsonPath('data.origin.id', $this->senderAddress->id)
        ->assertJsonPath('data.origin.postal_code', '1111111111')
        ->assertJsonPath('data.sender_address_postal_code', '1111111111')
        ->assertJsonPath('data.sender_address_city_code', 1101)
        ->assertJsonPath('data.sender_address_address', 'تهران، آدرس فرستنده')
        ->assertJsonPath('data.receiver_address_id', $this->receiverAddress->id)
        ->assertJsonPath('data.receiver_address.postal_code', '2222222222')
        ->assertJsonPath('data.descination.id', $this->receiverAddress->id)
        ->assertJsonPath('data.descination.postal_code', '2222222222')
        ->assertJsonPath('data.receiver_address_postal_code', '2222222222')
        ->assertJsonPath('data.receiver_address_city_code', 1101)
        ->assertJsonPath('data.receiver_address_address', 'تهران، آدرس گیرنده')
        ->assertJsonPath('data.sender_full_name', 'علی فرستنده')
        ->assertJsonPath('data.sender.full_name', 'علی فرستنده')
        ->assertJsonPath('data.receiver_full_name', 'رضا گیرنده')
        ->assertJsonPath('data.receiver.full_name', 'رضا گیرنده')
        ->assertJsonPath('data.driver1_national_code', '1234567890')
        ->assertJsonPath('data.driver1_full_name', 'حسین راننده')
        ->assertJsonPath('data.driver2_full_name', 'محمد کمک راننده')
        ->assertJsonPath('data.driver2_phone', '09122222222')
        ->assertJsonPath('data.referral_driver_id', $this->thirdDriver->id)
        ->assertJsonPath('data.referral_driver_full_name', 'عباس راننده حواله')
        ->assertJsonPath('data.referral_driver.phone_number_1', '09123333333')
        ->assertJsonPath('data.fleet.driver_license_type.name', 'پایه یک')
        ->assertJsonPath('data.fleet.loading_type.name', 'کفی')
        ->assertJsonPath('data.fleet.brand.name', 'بنز')
        ->assertJsonPath('data.fleet.type.name', 'اکتروس')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.description', 'توضیحات بارنامه')
        ->assertJsonPath('data.liability_insurance', $this->insurance->id)
        ->assertJsonPath('data.insurance.id', $this->insurance->id)
        ->assertJsonPath('data.insurance.title', $this->insurance->title)
        ->assertJsonPath('data.advance_freight_amount', 0)
        ->assertJsonPath('data.weighbridge_amount', 5000)
        ->assertJsonPath('data.commission_amount', 10000)
        ->assertJsonPath('data.insurance_amount', 2000)
        ->assertJsonPath('data.insurance_tax_amount', 10000)
        ->assertJsonPath('data.driver_receivable_amount', 22000)
        ->assertJsonPath('data.payable_amount', 132000)
        ->assertJsonCount(1, 'data.cargos')
        ->assertJsonPath('data.cargos.0.origin_weight', 1250.5)
        ->assertJsonPath('data.cargos.0.cargo_id', $this->cargo->id)
        ->assertJsonPath('data.cargos.0.cargo.code', $this->cargo->code)
        ->assertJsonPath('data.cargos.0.packaging_id', $this->packaging->id)
        ->assertJsonPath('data.cargos.0.packaging.code', $this->packaging->code)
        ->assertJsonPath('data.cargos.0.product_owner_id', $this->productOwner->id)
        ->assertJsonPath('data.cargos.0.product_owner.name', 'صاحب کالا')
        ->assertJsonPath('data.cargos.0.description', 'توضیحات محموله');

    expect($response->json('data.bijak_tracking_code'))
        ->toBeString()
        ->toHaveLength(25)
        ->toMatch('/^\d{4}1001\d{17}$/');

    $waybillId = $response->json('data.id');
    $this->sender->update(['first_name' => 'نام جدید']);
    $this->firstDriver->update(['first_name' => 'راننده جدید']);
    $this->senderAddress->update([
        'postal_code' => '3333333333',
        'address' => 'تهران، آدرس ویرایش‌شده فرستنده',
    ]);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.bijak_number', '1001')
        ->assertJsonPath('data.serial_number', 'SERIAL-1')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.description', 'توضیحات بارنامه')
        ->assertJsonPath('data.bijak_tracking_code', $response->json('data.bijak_tracking_code'))
        ->assertJsonPath('data.sender_full_name', 'علی فرستنده')
        ->assertJsonPath('data.driver1_full_name', 'حسین راننده')
        ->assertJsonPath('data.sender_address_postal_code', '1111111111')
        ->assertJsonPath('data.sender_address_address', 'تهران، آدرس فرستنده')
        ->assertJsonPath('data.sender.first_name', 'نام جدید')
        ->assertJsonPath('data.sender.full_name', 'نام جدید فرستنده')
        ->assertJsonPath('data.first_driver.first_name', 'راننده جدید')
        ->assertJsonPath('data.first_driver.full_name', 'راننده جدید راننده')
        ->assertJsonPath('data.sender_address.postal_code', '3333333333')
        ->assertJsonPath('data.sender_address.address', 'تهران، آدرس ویرایش‌شده فرستنده')
        ->assertJsonPath('data.origin.postal_code', '3333333333')
        ->assertJsonPath('data.origin.address', 'تهران، آدرس ویرایش‌شده فرستنده')
        ->assertJsonPath('data.receiver.last_name', 'گیرنده')
        ->assertJsonPath('data.referral_driver.last_name', 'راننده حواله')
        ->assertJsonPath('data.fleet.driver_license_type.code', 1)
        ->assertJsonPath('data.fleet.loading_type.code', 101)
        ->assertJsonPath('data.fleet.brand.brand_code', 10)
        ->assertJsonPath('data.fleet.type.tip_code', 1001)
        ->assertJsonCount(1, 'data.cargos');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'description' => 'تلاش برای ویرایش',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_edit_forbidden'));
});

test('completed and canceled waybills prevent deleting their references', function () {
    $waybillId = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->json('data.id');

    $this->deleteJson("/api/user/shipment-parties/{$this->sender->id}")
        ->assertUnprocessable();

    $this->patchJson("/api/user/waybills/{$waybillId}/cancel")
        ->assertSuccessful();

    $protectedUrls = [
        "/api/user/shipment-parties/{$this->sender->id}",
        "/api/user/shipment-parties/{$this->sender->id}/addresses/{$this->senderAddress->id}",
        "/api/user/drivers/{$this->firstDriver->id}",
        "/api/user/fleets/{$this->fleet->id}",
        "/api/user/product-owners/{$this->productOwner->id}",
        "/api/user/insurances/{$this->insurance->id}",
        "/api/user/transport-contracts/{$this->contract->id}",
    ];

    foreach ($protectedUrls as $url) {
        $this->deleteJson($url)->assertUnprocessable();
    }
});

test('an incomplete waybill does not prevent deleting its references', function () {
    $payload = completeWaybillPayload($this);
    $payload['status'] = WaybillStatus::Incomplete->value;

    $waybillId = $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->json('data.id');

    $deletableUrls = [
        "/api/user/shipment-parties/{$this->sender->id}/addresses/{$this->senderAddress->id}",
        "/api/user/shipment-parties/{$this->sender->id}",
        "/api/user/drivers/{$this->firstDriver->id}",
        "/api/user/fleets/{$this->fleet->id}",
        "/api/user/product-owners/{$this->productOwner->id}",
        "/api/user/insurances/{$this->insurance->id}",
        "/api/user/transport-contracts/{$this->contract->id}",
    ];

    foreach ($deletableUrls as $url) {
        $this->deleteJson($url)->assertSuccessful();
    }

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.liability_insurance', null)
        ->assertJsonPath('data.transport_contract_id', null);
});

test('editing a draft preserves its address snapshot', function () {
    $payload = completeWaybillPayload($this);
    $payload['status'] = WaybillStatus::Incomplete->value;

    $waybillId = $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.sender_address_postal_code', '1111111111')
        ->json('data.id');

    $this->senderAddress->update([
        'postal_code' => '4444444444',
        'address' => 'آدرس نهایی زمان صدور',
    ]);

    unset(
        $payload['bijak_number'],
        $payload['serial_number'],
        $payload['bijak_tracking_code'],
    );

    $this->putJson("/api/user/waybills/{$waybillId}", $payload)
        ->assertSuccessful()
        ->assertJsonPath('data.sender_address_postal_code', '1111111111')
        ->assertJsonPath('data.sender_address_address', 'تهران، آدرس فرستنده');
});

test('it filters waybills by searchable fields on related models', function () {
    $waybillId = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->json('data.id');

    $this->getJson('/api/user/waybills?fleet__plate_first_number=12')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $waybillId)
        ->assertJsonPath('data.0.fleet.plate.first_number', '12');

    $this->getJson('/api/user/waybills?fleet__plate_first_number=99')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/user/waybills?'.http_build_query([
        'senderAddress__city__name' => 'تهران',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $waybillId);

    $this->getJson('/api/user/waybills?'.http_build_query([
        'senderAddress__city__name' => 'شیراز',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('it stores an incomplete waybill', function () {
    $this->postJson('/api/user/waybills', ['status' => WaybillStatus::Incomplete->value])
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Incomplete->value)
        ->assertJsonCount(0, 'data.cargos');
});

test('waybill status is required', function () {
    $this->postJson('/api/user/waybills')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('it requires referral fields and issues a referral waybill through the inquiry service', function () {
    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Referral->value,
        'cargos' => [[]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id',
            'sender_address_id',
            'receiver_id',
            'receiver_address_id',
            'driver1_id',
            'referral_driver_id',
            'fleet_id',
            'loading_started_at',
            'loading_ended_at',
            'cargos.0.cargo_id',
            'cargos.0.packaging_id',
            'cargos.0.origin_weight',
        ]);

    $referralPayload = referralWaybillPayload($this);
    $referralWaybillId = $this->postJson('/api/user/waybills', $referralPayload)
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Referral->value)
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.insurance_amount', 0)
        ->assertJsonPath('data.serial_number', null)
        ->assertJsonPath('data.bijak_number', null)
        ->assertJsonPath('data.issued_at', null)
        ->assertJsonPath('data.bijak_tracking_code', null)
        ->json('data.id');

    $this->putJson("/api/user/waybills/{$referralWaybillId}", [
        ...$referralPayload,
        'referral_weight' => 1300,
        'quantity' => 11,
        'loading_started_at' => '2026-09-07 08:00:00',
        'loading_ended_at' => '2026-09-07 10:00:00',
        'description' => 'حواله ویرایش‌شده',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.referral_weight', '1300.000')
        ->assertJsonPath('data.description', 'حواله ویرایش‌شده')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.serial_number', null);
});

test('updating an incomplete waybill to referral uses create validation and reserves a referral number', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Referral->value,
        'cargos' => [[]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id',
            'sender_address_id',
            'receiver_id',
            'receiver_address_id',
            'driver1_id',
            'referral_driver_id',
            'fleet_id',
            'loading_started_at',
            'loading_ended_at',
            'cargos.0.cargo_id',
            'cargos.0.packaging_id',
            'cargos.0.origin_weight',
        ]);

    $this->putJson("/api/user/waybills/{$waybillId}", referralWaybillPayload($this))
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Referral->value)
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.serial_number', null)
        ->assertJsonPath('data.bijak_number', null);

    $this->getJson("/api/user/referral-numbers/{$this->referralNumberId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 1);
});

test('referral inquiry assigns the next number and canceled numbers cannot be reused', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
    ])->assertCreated()->json('data.id');

    $this->postJson('/api/user/referral-numbers/inquiry')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('waybill_id');

    $this->postJson('/api/user/referral-numbers/inquiry', [
        'waybill_id' => $waybillId,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $waybillId)
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.serial_number', null);

    $this->patchJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'description' => 'پیش‌نویس ویرایش‌شده پس از استعلام',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Referral->value)
        ->assertJsonPath('data.description', 'پیش‌نویس ویرایش‌شده پس از استعلام')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'status' => WaybillStatus::Referral->value,
        'referral_number' => '1',
        'referral_serial' => 'SERIAL-1',
    ]);

    $this->postJson('/api/user/referral-numbers/inquiry', [
        'waybill_id' => $waybillId,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_referral_already_assigned'));

    $this->patchJson("/api/user/waybills/{$waybillId}/referral/cancel")
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', null);

    $this->getJson("/api/user/referral-numbers/{$this->referralNumberId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 1);

    $this->postJson('/api/user/referral-numbers/inquiry', [
        'waybill_id' => $waybillId,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', '2');

    $this->patchJson("/api/user/waybills/{$waybillId}/referral/cancel")
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', null);

    $referralPayload = referralWaybillPayload($this);

    $createdWaybillId = $this->postJson('/api/user/waybills', $referralPayload)
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '3')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.serial_number', null)
        ->json('data.id');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $createdWaybillId,
        'status' => WaybillStatus::Referral->value,
        'referral_number' => '3',
        'referral_serial' => 'SERIAL-1',
        'serial_number' => null,
    ]);
});

test('a completed waybill does not require referral-only fields', function () {
    $payload = completeWaybillPayload($this);
    unset(
        $payload['referral_weight'],
        $payload['quantity'],
        $payload['loading_started_at'],
        $payload['loading_ended_at'],
        $payload['referral_number'],
    );

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Completed->value)
        ->assertJsonPath('data.referral_number', '1');
});

test('insurance amount accepts zero for referral and requires at least one for completed', function () {
    $this->postJson('/api/user/waybills', referralWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.insurance_amount', 0);

    $completedPayload = completeWaybillPayload($this);
    $completedPayload['insurance_amount'] = 0;

    $this->postJson('/api/user/waybills', $completedPayload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('insurance_amount');

    $completedPayload['insurance_amount'] = 1;

    $this->postJson('/api/user/waybills', $completedPayload)
        ->assertCreated()
        ->assertJsonPath('data.insurance_amount', 1);
});

test('a completed waybill requires its document fields', function () {
    $payload = completeWaybillPayload($this);
    unset(
        $payload['bijak_number'],
        $payload['serial_number'],
        $payload['issued_at'],
        $payload['liability_insurance'],
    );

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'bijak_number',
            'serial_number',
            'issued_at',
            'liability_insurance',
        ]);
});

test('a completed waybill stores the requested issuance time and issuer print name', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-27 14:35:42', 'Asia/Tehran'));
    $payload = completeWaybillPayload($this);
    $payload['issued_at'] = '2026-09-25 08:15:30';

    $created = $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.issued_at', '2026-09-25 08:15:30')
        ->assertJsonPath('data.issued_by_print_name', 'کاربر صادرکننده');
    $waybillId = $created->json('data.id');

    $this->user->update(['print_name' => 'نام چاپی جدید']);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.issued_by_print_name', 'کاربر صادرکننده');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'issued_at' => '2026-09-25 08:15:30',
        'issued_by_print_name' => 'کاربر صادرکننده',
    ]);
});

test('a completed waybill requires issuance date and time in the expected format', function () {
    $payload = completeWaybillPayload($this);
    $payload['issued_at'] = '2026-09-25';

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('issued_at');
});

test('it prevents creating a canceled waybill directly', function () {
    $this->postJson('/api/user/waybills', ['status' => WaybillStatus::Canceled->value])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_direct_cancel_forbidden'));

    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Canceled->value,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_direct_cancel_forbidden'));
});

test('canceling a completed waybill preserves its issued data and cargos', function () {
    $created = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated();

    $waybillId = $created->json('data.id');
    $trackingCode = $created->json('data.bijak_tracking_code');

    $this->patchJson("/api/user/waybills/{$waybillId}/cancel")
        ->assertSuccessful()
        ->assertJsonPath('message', __('public.waybill_canceled_success'))
        ->assertJsonPath('data.status', WaybillStatus::Canceled->value)
        ->assertJsonPath('data.bijak_number', '1001')
        ->assertJsonPath('data.serial_number', 'SERIAL-1')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.bijak_tracking_code', $trackingCode)
        ->assertJsonCount(1, 'data.cargos');

    $this->patchJson("/api/user/waybills/{$waybillId}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_cancel_status_invalid'));
});

test('only a completed waybill can be canceled', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/user/waybills/{$waybillId}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_cancel_status_invalid'));
});

test('an incomplete waybill never stores issuance fields', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'bijak_number' => 9001,
        'serial_number' => 'DRAFT-SERIAL',
        'issued_at' => '2026-09-07 11:00:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.bijak_number', null)
        ->assertJsonPath('data.serial_number', null)
        ->assertJsonPath('data.issued_at', null)
        ->assertJsonPath('data.issued_by_print_name', null)
        ->json('data.id');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'bijak_number' => 9002,
        'serial_number' => 'UPDATED-DRAFT-SERIAL',
        'bijak_tracking_code' => 'UPDATED-TRACKING-CODE',
        'issued_at' => '2026-09-08 11:00:00',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Incomplete->value)
        ->assertJsonPath('data.bijak_number', null)
        ->assertJsonPath('data.serial_number', null)
        ->assertJsonPath('data.bijak_tracking_code', null)
        ->assertJsonPath('data.issued_at', null)
        ->assertJsonPath('data.issued_by_print_name', null);

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'bijak_number' => null,
        'serial_number' => null,
        'issued_at' => null,
        'issued_by_print_name' => null,
    ]);
});

test('an incomplete waybill can be completed through update', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
    ])->assertCreated()->json('data.id');

    $issuer = User::factory()->create([
        'first_name' => 'اپراتور',
        'last_name' => 'نهایی صدور',
        'print_name' => null,
    ]);
    $this->app['auth']->forgetGuards();
    $this->withToken($issuer->createToken(
        'waybill-issuer-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);

    $this->putJson("/api/user/waybills/{$waybillId}", completeWaybillPayload($this))
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Completed->value)
        ->assertJsonPath('data.bijak_number', '1001')
        ->assertJsonPath('data.serial_number', 'SERIAL-1')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.issued_by_print_name', 'اپراتور نهایی صدور')
        ->assertJsonPath('data.issued_at', '2026-09-07 11:00:00');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'created_by' => $this->user->id,
        'issued_by_print_name' => 'اپراتور نهایی صدور',
    ]);
});

test('an incomplete waybill accepts null values without requiring any other field', function () {
    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'sender_id' => null,
        'sender_address_id' => null,
        'receiver_id' => null,
        'receiver_address_id' => null,
        'driver1_id' => null,
        'driver2_id' => null,
        'referral_driver_id' => null,
        'fleet_id' => null,
        'transport_contract_id' => null,
        'base_freight_amount' => null,
        'bijak_number' => null,
        'payable_amount' => null,
        'freight_at_origin' => null,
        'is_fixed' => null,
        'cargos' => null,
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Incomplete->value)
        ->assertJsonPath('data.bijak_number', null)
        ->assertJsonPath('data.freight_at_origin', false)
        ->assertJsonPath('data.is_fixed', false)
        ->assertJsonCount(0, 'data.cargos');
});

test('a complete waybill requires a non empty bijak number', function () {
    $payload = completeWaybillPayload($this);
    $payload['bijak_number'] = null;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bijak_number');
});

test('a completed waybill number and serial must belong to the active bijak range', function () {
    $outsideRange = completeWaybillPayload($this);
    $outsideRange['bijak_number'] = 2001;

    $this->postJson('/api/user/waybills', $outsideRange)
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_bijak_range_invalid'));

    $wrongSerial = completeWaybillPayload($this);
    $wrongSerial['serial_number'] = 'WRONG-SERIAL';

    $this->postJson('/api/user/waybills', $wrongSerial)
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_bijak_range_invalid'));

    $this->getJson("/api/user/bijak-numbers/{$this->bijakNumberId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', null);
});

test('disabled first available setting allows any unused number in the active range', function () {
    $payload = completeWaybillPayload($this);
    $payload['bijak_number'] = 1500;

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.bijak_number', '1500');
});

test('enabled first available setting enforces sequential waybill numbers', function () {
    $this->putJson('/api/user/settings', [
        'general' => [
            'assign_first_available_waybill_number' => true,
        ],
    ])->assertSuccessful();

    $payload = completeWaybillPayload($this);

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.error.0',
            __('public.waybill_first_available_bijak_required', ['number' => 1]),
        );

    $payload['bijak_number'] = 1;
    $firstWaybillId = $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.bijak_number', '1')
        ->json('data.id');

    $this->patchJson("/api/user/waybills/{$firstWaybillId}/cancel")
        ->assertSuccessful();

    $payload['bijak_number'] = 3;
    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.error.0',
            __('public.waybill_first_available_bijak_required', ['number' => 2]),
        );

    $payload['bijak_number'] = 2;
    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.bijak_number', '2');
});

test('enabling sequential assignment uses the first real gap in previously issued numbers', function () {
    $payload = completeWaybillPayload($this);
    $payload['bijak_number'] = 2;

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated();

    $this->putJson('/api/user/settings', [
        'general' => [
            'assign_first_available_waybill_number' => true,
        ],
    ])->assertSuccessful();

    $payload['bijak_number'] = 3;
    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.error.0',
            __('public.waybill_first_available_bijak_required', ['number' => 1]),
        );

    $payload['bijak_number'] = 1;
    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated();

    $payload['bijak_number'] = 3;
    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.bijak_number', '3');
});

test('an incomplete fixed waybill does not require a payable amount', function () {
    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'is_fixed' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Incomplete->value)
        ->assertJsonPath('data.is_fixed', true)
        ->assertJsonPath('data.payable_amount', null);
});

test('an incomplete waybill can store a partially filled cargo row', function () {
    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'cargos' => [[
            'title' => 'محموله نیمه‌کاره',
            'description' => 'ادامه اطلاعات بعداً ثبت می‌شود',
        ]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Incomplete->value)
        ->assertJsonPath('data.cargos.0.title', 'محموله نیمه‌کاره')
        ->assertJsonPath('data.cargos.0.cargo_id', null)
        ->assertJsonPath('data.cargos.0.packaging_id', null);
});

test('it accepts a cargo without a product owner or description', function () {
    $payload = completeWaybillPayload($this);
    unset($payload['cargos'][0]['product_owner_id'], $payload['cargos'][0]['description']);

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.cargos.0.product_owner_id', null)
        ->assertJsonPath('data.cargos.0.description', null);
});

test('it rejects unknown cargo and packaging codes and a product owner from another company', function () {
    $payload = completeWaybillPayload($this);
    $payload['cargos'][0]['cargo_id'] = 99999999;
    $payload['cargos'][0]['packaging_id'] = 99999999;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cargos.0.cargo_id', 'cargos.0.packaging_id']);

    $otherCompany = Company::factory()->create();
    $productOwnerRepository = app(ProductOwnerRepositoryInterface::class);
    $productOwnerRepository->create($otherCompany->id, ['name' => 'صاحب کالای دیگر']);
    $otherOwner = $productOwnerRepository->create($otherCompany->id, ['name' => 'صاحب کالای دوم']);
    $payload = completeWaybillPayload($this);
    $payload['cargos'][0]['product_owner_id'] = $otherOwner->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cargos.0.product_owner_id');
});

test('it reserves document numbers when a waybill is created or updated to completed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-27 16:20:30', 'Asia/Tehran'));

    $draftId = $this->postJson('/api/user/waybills', ['status' => WaybillStatus::Incomplete->value])
        ->assertCreated()
        ->json('data.id');

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 1);

    $issuedId = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.bijak_number', '1001')
        ->assertJsonPath('data.serial_number', 'SERIAL-1')
        ->json('data.id');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $issuedId,
        'bijak_number' => '1001',
        'serial_number' => 'SERIAL-1',
        'issued_at' => '2026-09-27 16:20:30',
    ]);

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 2);

    $updatePayload = completeWaybillPayload($this);
    $updatePayload['bijak_number'] = 1002;

    $this->putJson("/api/user/waybills/{$draftId}", $updatePayload)
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Completed->value)
        ->assertJsonPath('data.referral_number', '2')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->assertJsonPath('data.bijak_number', '1002')
        ->assertJsonPath('data.serial_number', 'SERIAL-1');

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 3);
});

test('waybill document numbers have company scoped unique indexes', function () {
    $tableName = "company_{$this->company->id}_waybills";

    expect(Schema::hasIndex($tableName, "{$tableName}_referral_unique"))->toBeTrue()
        ->and(Schema::hasIndex($tableName, "{$tableName}_serial_referral_unique"))->toBeFalse()
        ->and(Schema::hasIndex($tableName, "{$tableName}_serial_bijak_unique"))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'status'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'is_incomplete'))->toBeFalse();
});

test('waybill number checks are scoped to the exact company owner', function () {
    $branch = Company::factory()->create([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
    ]);
    $tableName = "company_{$this->company->id}_waybills";

    DB::table($tableName)->insert([
        'owner_company_id' => $branch->id,
        'serial_number' => 'SHARED-SERIAL',
        'referral_serial' => 'SHARED-SERIAL',
        'referral_number' => '5001',
        'bijak_number' => '6001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = app(WaybillRepositoryInterface::class);

    expect($repository->referralNumberExists($this->company->id, 'SHARED-SERIAL', '5001'))->toBeFalse()
        ->and($repository->bijakNumberExists($this->company->id, 'SHARED-SERIAL', '6001'))->toBeFalse()
        ->and($repository->referralNumberExists($branch->id, 'SHARED-SERIAL', '5001'))->toBeTrue()
        ->and($repository->bijakNumberExists($branch->id, 'SHARED-SERIAL', '6001'))->toBeTrue();
});

test('non unique database errors are not reported as duplicate waybill numbers', function () {
    $payload = completeWaybillPayload($this);
    $payload['created_by'] = 999999999;

    expect(fn () => app(WaybillService::class)->create($this->company->id, $payload))
        ->toThrow(QueryException::class);
});

test('database rejects duplicate referral numbers and bijak numbers in the same serial', function () {
    $tableName = "company_{$this->company->id}_waybills";
    $base = [
        'owner_company_id' => $this->company->id,
        'serial_number' => 'DB-SERIAL',
        'referral_serial' => 'DB-REF-SERIAL',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table($tableName)->insert([
        ...$base,
        'referral_number' => '7001',
        'bijak_number' => '8001',
    ]);

    expect(fn () => DB::table($tableName)->insert([
        ...$base,
        'serial_number' => 'OTHER-SERIAL',
        'referral_number' => '7001',
        'bijak_number' => '8002',
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table($tableName)->insert([
        ...$base,
        'referral_number' => '7002',
        'bijak_number' => '8001',
    ]))->toThrow(QueryException::class);
});

test('it rejects a used bijak number in the same serial without consuming a referral number', function () {
    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated();

    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertUnprocessable()
        ->assertJsonPath(
            'errors.error.0',
            __('public.waybill_bijak_number_used', [
                'number' => 1001,
                'serial' => 'SERIAL-1',
            ]),
        );

    $this->getJson("/api/user/referral-numbers/{$this->referralNumberId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 1);
});

test('it advances past assigned referral numbers when the range counter is stale', function () {
    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated();

    DB::table("company_{$this->company->id}_waybills")->insert([
        'owner_company_id' => $this->company->id,
        'status' => WaybillStatus::Completed->value,
        'referral_serial' => 'SERIAL-1',
        'referral_number' => '3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table("company_{$this->company->id}_referral_numbers")
        ->where('id', $this->referralNumberId)
        ->update(['last_number' => null]);

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 4);

    $waybillId = $this->postJson('/api/user/waybills', referralWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '4')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1')
        ->json('data.id');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'referral_serial' => 'SERIAL-1',
        'referral_number' => '4',
    ]);

    $this->getJson("/api/user/referral-numbers/{$this->referralNumberId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 4);
});

test('it completes the referral range at the final issued waybill', function () {
    $rangeId = $this->referralNumberId;

    $this->patchJson("/api/user/referral-numbers/{$rangeId}", ['to_number' => 1])
        ->assertSuccessful();

    $payload = completeWaybillPayload($this);
    $payload['bijak_number'] = 1;

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '1');

    $this->getJson("/api/user/referral-numbers/{$rangeId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 1)
        ->assertJsonPath('data.status', 'completed');

    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();
    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.referral_issuance_unavailable'));
});

test('the default range completes after issuing number 999999', function () {
    $this->patchJson("/api/user/referral-numbers/{$this->referralNumberId}", [
        'status' => 'inactive',
    ])->assertSuccessful();

    $tableName = "company_{$this->company->id}_referral_numbers";
    $defaultId = DB::table($tableName)->where('title', 'پیش فرض')->value('id');
    DB::table($tableName)
        ->where('id', $defaultId)
        ->update(['last_number' => 999998]);
    $this->patchJson("/api/user/referral-numbers/{$defaultId}", [
        'status' => 'active',
    ])->assertSuccessful();

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 999999)
        ->assertJsonPath('data.serial_number', '1405');

    $payload = completeWaybillPayload($this);

    $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '999999')
        ->assertJsonPath('data.serial_number', 'SERIAL-1');

    $this->getJson("/api/user/referral-numbers/{$defaultId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 999999)
        ->assertJsonPath('data.status', 'completed');
    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();
});

test('it generates a 25 digit tracking code from the Jalali year and bijak number', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-27 12:00:00'));

    $trackingCode = app(WaybillTrackingCodeGenerator::class)
        ->generate($this->company->id, '987654');

    expect($trackingCode)
        ->toHaveLength(25)
        ->toMatch('/^1405987654\d{15}$/');
});

test('the referral driver can be the first or second driver', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-27 12:00:00'));

    $firstDriverPayload = completeWaybillPayload($this);
    $firstDriverPayload['referral_driver_id'] = $this->firstDriver->id;

    $firstResponse = $this->postJson('/api/user/waybills', $firstDriverPayload)
        ->assertCreated()
        ->assertJsonPath('data.referral_driver_id', $this->firstDriver->id)
        ->assertJsonPath('data.referral_driver_national_code', '1234567890');

    $secondDriverPayload = completeWaybillPayload($this);
    $secondDriverPayload['referral_driver_id'] = $this->secondDriver->id;
    $secondDriverPayload['bijak_number'] = 1002;

    $secondResponse = $this->postJson('/api/user/waybills', $secondDriverPayload)
        ->assertCreated()
        ->assertJsonPath('data.referral_driver_id', $this->secondDriver->id)
        ->assertJsonPath('data.referral_driver_national_code', '0987654321');

    expect($firstResponse->json('data.bijak_tracking_code'))
        ->toMatch('/^14051001\d{17}$/')
        ->and($secondResponse->json('data.bijak_tracking_code'))
        ->toMatch('/^14051002\d{17}$/')
        ->not->toBe($firstResponse->json('data.bijak_tracking_code'));
});

test('it validates all required complete waybill data and company references', function () {
    $this->postJson('/api/user/waybills', ['status' => WaybillStatus::Completed->value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id', 'sender_address_id', 'receiver_id', 'receiver_address_id',
            'driver1_id', 'referral_driver_id', 'fleet_id', 'transport_contract_id',
            'base_freight_amount', 'cargos',
        ]);

    $payload = completeWaybillPayload($this);
    $payload['sender_id'] = $this->receiver->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sender_id');

    $payload = completeWaybillPayload($this);
    $payload['sender_address_id'] = $this->receiverAddress->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sender_address_id');

    $payload = completeWaybillPayload($this);
    $payload['receiver_address_id'] = $this->senderAddress->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('receiver_address_id');

    $otherCompanyInsurance = Insurance::factory()->create();
    $payload = completeWaybillPayload($this);
    $payload['liability_insurance'] = $otherCompanyInsurance->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('liability_insurance');

    $otherCompany = Company::factory()->create();
    $payload = completeWaybillPayload($this);
    $payload['transport_contract_id'] = $otherCompany->transportContracts()->sole()->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transport_contract_id');
});

test('it uses Persian attribute names in waybill validation messages', function () {
    $response = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Completed->value,
        'cargos' => [[
            'cargo_id' => null,
            'packaging_id' => null,
            'is_returned' => null,
        ]],
    ])->assertUnprocessable();

    $response
        ->assertJsonPath('errors.base_freight_amount.0', 'تکمیل گزینه مبلغ کرایه پایه الزامی است')
        ->assertJsonPath('errors.bijak_number.0', 'تکمیل گزینه شماره بیجک الزامی است')
        ->assertJsonPath('errors.sender_id.0', 'تکمیل گزینه فرستنده الزامی است');

    expect($response->json('errors'))
        ->toHaveKey('cargos.0.cargo_id', ['تکمیل گزینه کد محموله الزامی است'])
        ->toHaveKey('cargos.0.packaging_id', ['تکمیل گزینه کد دسته‌بندی بسته‌بندی الزامی است'])
        ->toHaveKey('cargos.0.is_returned', ['تکمیل گزینه برگشتی بودن محموله الزامی است']);
});

test('a waybill accepts at most ten cargos', function () {
    $payload = completeWaybillPayload($this);
    $payload['cargos'] = array_fill(0, 10, $payload['cargos'][0]);

    $waybillId = $this->postJson('/api/user/waybills', $payload)
        ->assertCreated()
        ->assertJsonCount(10, 'data.cargos')
        ->json('data.id');

    $payload['cargos'][] = $payload['cargos'][0];

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cargos');

    $this->putJson("/api/user/waybills/{$waybillId}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cargos');
});

test('completed and canceled waybills cannot be edited', function () {
    $waybillId = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->json('data.id');

    $this->patchJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'description' => 'ویرایش غیرمجاز',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_edit_forbidden'));

    $this->patchJson("/api/user/waybills/{$waybillId}/cancel")
        ->assertSuccessful();

    $this->patchJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'description' => 'ویرایش غیرمجاز',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.error.0', __('public.waybill_edit_forbidden'));
});

test('editing a referral waybill keeps its referral status when incomplete is requested', function () {
    $waybillId = $this->postJson('/api/user/waybills', referralWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.status', WaybillStatus::Referral->value)
        ->json('data.id');

    $this->putJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'description' => 'ویرایش حواله بدون تغییر وضعیت',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', WaybillStatus::Referral->value)
        ->assertJsonPath('data.description', 'ویرایش حواله بدون تغییر وضعیت')
        ->assertJsonPath('data.referral_number', '1')
        ->assertJsonPath('data.referral_serial', 'SERIAL-1');

    $this->assertDatabaseHas("company_{$this->company->id}_waybills", [
        'id' => $waybillId,
        'status' => WaybillStatus::Referral->value,
        'referral_number' => '1',
        'referral_serial' => 'SERIAL-1',
    ]);
});

/** @return array<string, mixed> */
function completeWaybillPayload(object $test): array
{
    return [
        'status' => WaybillStatus::Completed->value,
        'sender_id' => $test->sender->id,
        'sender_address_id' => $test->senderAddress->id,
        'receiver_id' => $test->receiver->id,
        'receiver_address_id' => $test->receiverAddress->id,
        'driver1_id' => $test->firstDriver->id,
        'driver2_id' => $test->secondDriver->id,
        'referral_driver_id' => $test->thirdDriver->id,
        'fleet_id' => $test->fleet->id,
        'referral_weight' => 1250.5,
        'quantity' => 10,
        'loading_started_at' => '2026-09-07 08:00:00',
        'loading_ended_at' => '2026-09-07 10:00:00',
        'referral_number' => 'REF-1',
        'bijak_number' => 1001,
        'serial_number' => 'SERIAL-1',
        'issued_at' => '2026-09-07 11:00:00',
        'liability_insurance' => $test->insurance->id,
        'bijak_tracking_code' => 'FRONT-CODE',
        'description' => 'توضیحات بارنامه',
        'transport_contract_id' => $test->contract->id,
        'base_freight_amount' => 100000,
        'weighbridge_amount' => 0,
        'insurance_amount' => 1000,
        'insurance_tax_amount' => 100,
        'detention_amount' => 5000,
        'payable_amount' => 106100,
        'freight_at_origin' => true,
        'is_fixed' => false,
        'cargos' => [[
            'cargo_id' => $test->cargo->code,
            'packaging_id' => $test->packaging->code,
            'product_owner_id' => $test->productOwner->id,
            'description' => 'توضیحات محموله',
            'title' => 'محموله گندم',
            'origin_weight' => 1250.5,
            'value' => 50000000,
            'quantity' => 10,
            'is_traffic' => false,
            'is_returned' => true,
            'cottage_number' => null,
            'cottage_number_2' => null,
            'driver_account_number' => 'IR-123',
            'container_number' => 'CONT-1',
            'container_number_2' => null,
        ]],
    ];
}

/** @return array<string, mixed> */
function referralWaybillPayload(object $test): array
{
    return [
        'status' => WaybillStatus::Referral->value,
        'sender_id' => $test->sender->id,
        'sender_address_id' => $test->senderAddress->id,
        'receiver_id' => $test->receiver->id,
        'receiver_address_id' => $test->receiverAddress->id,
        'driver1_id' => $test->firstDriver->id,
        'referral_driver_id' => $test->thirdDriver->id,
        'fleet_id' => $test->fleet->id,
        'referral_weight' => 1250.5,
        'quantity' => 10,
        'loading_started_at' => '2026-09-07 08:00:00',
        'loading_ended_at' => '2026-09-07 10:00:00',
        'insurance_amount' => 0,
        'cargos' => [[
            'cargo_id' => $test->cargo->code,
            'packaging_id' => $test->packaging->code,
            'origin_weight' => 1250.5,
        ]],
    ];
}
