<?php

use App\Enums\TransportContractItemName;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Cargo;
use App\Models\City;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\Insurance;
use App\Models\Packaging;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
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

    $repository = app(CompanyDataRepositoryInterface::class);
    $this->productOwner = $repository->create($this->company->id, 'product_owner', [
        'name' => 'صاحب کالا',
        'phone' => '09120000003',
    ]);
    $this->sender = $repository->create($this->company->id, 'shipment_parties', [
        'national_identifier' => '10101010101',
        'is_sender' => true,
        'is_receiver' => false,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'فرستنده',
        'mobile' => '09120000001',
    ]);
    $this->receiver = $repository->create($this->company->id, 'shipment_parties', [
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
    $this->senderAddress = $repository->create($this->company->id, 'shipment_party_addresses', [
        'shipment_party_id' => $this->sender->id,
        'postal_code' => '1111111111',
        'city_code' => $city->code,
        'address' => 'تهران، آدرس فرستنده',
    ]);
    $this->receiverAddress = $repository->create($this->company->id, 'shipment_party_addresses', [
        'shipment_party_id' => $this->receiver->id,
        'postal_code' => '2222222222',
        'city_code' => $city->code,
        'address' => 'تهران، آدرس گیرنده',
    ]);

    $licenseType = DriverLicenseType::query()->create(['name' => 'پایه یک', 'code' => 1]);
    $driverData = [
        'father_name' => 'حسن',
        'license_type' => $licenseType->id,
        'license_expiry_date' => '2030-01-01',
        'status' => 'active',
    ];
    $this->firstDriver = $repository->create($this->company->id, 'drivers', [
        ...$driverData,
        'national_code' => '1234567890',
        'first_name' => 'حسین',
        'last_name' => 'راننده',
        'license_number' => 'LIC-1',
        'phone_number_1' => '09121111111',
    ]);
    $this->secondDriver = $repository->create($this->company->id, 'drivers', [
        ...$driverData,
        'national_code' => '0987654321',
        'first_name' => 'محمد',
        'last_name' => 'کمک راننده',
        'license_number' => 'LIC-2',
        'phone_number_1' => '09122222222',
    ]);
    $this->thirdDriver = $repository->create($this->company->id, 'drivers', [
        ...$driverData,
        'national_code' => '1122334455',
        'first_name' => 'عباس',
        'last_name' => 'راننده حواله',
        'license_number' => 'LIC-3',
        'phone_number_1' => '09123333333',
    ]);
    $this->fleet = $repository->create($this->company->id, 'fleets', [
        'status' => 'active',
        'ownership_type' => 'owned',
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'has_violation' => false,
    ]);
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
        'to_number' => 10,
    ])->assertCreated()->json('data.id');
});

test('it creates a complete waybill with snapshots cargos and calculated contract amounts', function () {
    $response = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.sender_address_id', $this->senderAddress->id)
        ->assertJsonPath('data.sender_address.postal_code', '1111111111')
        ->assertJsonPath('data.receiver_address_id', $this->receiverAddress->id)
        ->assertJsonPath('data.receiver_address.postal_code', '2222222222')
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.receiver_last_name', 'گیرنده')
        ->assertJsonPath('data.driver1_national_code', '1234567890')
        ->assertJsonPath('data.driver2_phone', '09122222222')
        ->assertJsonPath('data.referral_driver_id', $this->thirdDriver->id)
        ->assertJsonPath('data.referral_driver_first_name', 'عباس')
        ->assertJsonPath('data.referral_driver.phone_number_1', '09123333333')
        ->assertJsonPath('data.referral_number', '2')
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
        ->toMatch('/^\d{8}$/');

    $waybillId = $response->json('data.id');
    $cargoItemId = $response->json('data.cargos.0.id');
    $this->sender->update(['first_name' => 'نام جدید']);
    $this->firstDriver->update(['first_name' => 'راننده جدید']);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.bijak_number', 'BIJAK-1')
        ->assertJsonPath('data.serial_number', 'SERIAL-1')
        ->assertJsonPath('data.referral_number', '2')
        ->assertJsonPath('data.description', 'توضیحات بارنامه')
        ->assertJsonPath('data.bijak_tracking_code', $response->json('data.bijak_tracking_code'))
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.driver1_first_name', 'حسین')
        ->assertJsonPath('data.sender.first_name', 'نام جدید')
        ->assertJsonPath('data.first_driver.first_name', 'راننده جدید')
        ->assertJsonPath('data.receiver.last_name', 'گیرنده')
        ->assertJsonPath('data.referral_driver.last_name', 'راننده حواله')
        ->assertJsonCount(1, 'data.cargos');

    $updatePayload = completeWaybillPayload($this);
    $updatePayload['is_fixed'] = true;
    $updatePayload['payable_amount'] = 999999;
    $updatePayload['referral_driver_id'] = $this->firstDriver->id;

    $this->putJson("/api/user/waybills/{$waybillId}", $updatePayload)
        ->assertSuccessful()
        ->assertJsonPath('data.payable_amount', 999999)
        ->assertJsonPath('data.bijak_tracking_code', $response->json('data.bijak_tracking_code'))
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.referral_driver_id', $this->firstDriver->id)
        ->assertJsonPath('data.referral_driver_first_name', 'راننده جدید')
        ->assertJsonPath('data.referral_number', '2')
        ->assertJsonPath('data.cargos.0.id', $cargoItemId);
});

test('it stores an incomplete waybill', function () {
    $this->postJson('/api/user/waybills', ['is_incomplete' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
        ->assertJsonCount(0, 'data.cargos');
});

test('an incomplete waybill accepts null values without requiring any other field', function () {
    $this->postJson('/api/user/waybills', [
        'is_incomplete' => true,
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
        'payable_amount' => null,
        'freight_at_origin' => null,
        'is_fixed' => null,
        'cargos' => null,
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
        ->assertJsonPath('data.freight_at_origin', false)
        ->assertJsonPath('data.is_fixed', false)
        ->assertJsonCount(0, 'data.cargos');
});

test('an incomplete fixed waybill does not require a payable amount', function () {
    $this->postJson('/api/user/waybills', [
        'is_incomplete' => true,
        'is_fixed' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
        ->assertJsonPath('data.is_fixed', true)
        ->assertJsonPath('data.payable_amount', null);
});

test('an incomplete waybill can store a partially filled cargo row', function () {
    $this->postJson('/api/user/waybills', [
        'is_incomplete' => true,
        'cargos' => [[
            'title' => 'محموله نیمه‌کاره',
            'description' => 'ادامه اطلاعات بعداً ثبت می‌شود',
        ]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
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
    $otherRepository = app(CompanyDataRepositoryInterface::class);
    $otherRepository->create($otherCompany->id, 'product_owner', ['name' => 'صاحب کالای دیگر']);
    $otherOwner = $otherRepository->create($otherCompany->id, 'product_owner', ['name' => 'صاحب کالای دوم']);
    $payload = completeWaybillPayload($this);
    $payload['cargos'][0]['product_owner_id'] = $otherOwner->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cargos.0.product_owner_id');
});

test('it reserves a referral number only when a waybill is issued', function () {
    $draftId = $this->postJson('/api/user/waybills', ['is_incomplete' => true])
        ->assertCreated()
        ->json('data.id');

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 2);

    $this->putJson("/api/user/waybills/{$draftId}", completeWaybillPayload($this))
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', '2')
        ->assertJsonPath('data.serial_number', 'SERIAL-1');

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 3);

    $this->putJson("/api/user/waybills/{$draftId}", [
        ...completeWaybillPayload($this),
        'referral_number' => '999',
        'serial_number' => 'WRONG',
    ])->assertSuccessful()
        ->assertJsonPath('data.referral_number', '2')
        ->assertJsonPath('data.serial_number', 'SERIAL-1');

    $this->getJson('/api/user/referral-numbers/inquiry')
        ->assertSuccessful()
        ->assertJsonPath('data.referral_number', 3);
});

test('it completes the referral range at the final issued waybill', function () {
    $rangeId = $this->referralNumberId;

    $this->patchJson("/api/user/referral-numbers/{$rangeId}", ['to_number' => 2])
        ->assertSuccessful();

    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '2');

    $this->getJson("/api/user/referral-numbers/{$rangeId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 2)
        ->assertJsonPath('data.status', 'completed');

    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();
    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('referral_number');
});

test('the default range completes after issuing number 999999', function () {
    $this->patchJson("/api/user/referral-numbers/{$this->referralNumberId}", [
        'status' => 'inactive',
    ])->assertSuccessful();

    $tableName = "company_{$this->company->id}_referral_numbers";
    $defaultId = DB::table($tableName)->where('title', 'پیشفرض')->value('id');
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

    $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.referral_number', '999999')
        ->assertJsonPath('data.serial_number', '1405');

    $this->getJson("/api/user/referral-numbers/{$defaultId}")
        ->assertSuccessful()
        ->assertJsonPath('data.last_number', 999999)
        ->assertJsonPath('data.status', 'completed');
    $this->getJson('/api/user/referral-numbers/inquiry')->assertNotFound();
});

test('the referral driver can be the first or second driver', function () {
    $firstDriverPayload = completeWaybillPayload($this);
    $firstDriverPayload['referral_driver_id'] = $this->firstDriver->id;

    $firstResponse = $this->postJson('/api/user/waybills', $firstDriverPayload)
        ->assertCreated()
        ->assertJsonPath('data.referral_driver_id', $this->firstDriver->id)
        ->assertJsonPath('data.referral_driver_national_code', '1234567890');

    $secondDriverPayload = completeWaybillPayload($this);
    $secondDriverPayload['referral_driver_id'] = $this->secondDriver->id;

    $secondResponse = $this->postJson('/api/user/waybills', $secondDriverPayload)
        ->assertCreated()
        ->assertJsonPath('data.referral_driver_id', $this->secondDriver->id)
        ->assertJsonPath('data.referral_driver_national_code', '0987654321');

    expect($secondResponse->json('data.bijak_tracking_code'))
        ->not->toBe($firstResponse->json('data.bijak_tracking_code'));
});

test('it validates all required complete waybill data and company references', function () {
    $this->postJson('/api/user/waybills', ['is_incomplete' => false])
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
        'is_incomplete' => false,
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

test('it requires the same complete body when updating a complete waybill', function () {
    $waybillId = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->json('data.id');

    $this->patchJson("/api/user/waybills/{$waybillId}", [
        'is_incomplete' => false,
        'quantity' => 20,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id', 'sender_address_id', 'receiver_id', 'receiver_address_id',
            'driver1_id', 'referral_driver_id', 'fleet_id', 'transport_contract_id',
            'base_freight_amount', 'cargos',
        ]);
});

/** @return array<string, mixed> */
function completeWaybillPayload(object $test): array
{
    return [
        'is_incomplete' => false,
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
        'bijak_number' => 'BIJAK-1',
        'serial_number' => 'SERIAL-1',
        'issued_at' => '2026-09-07 11:00:00',
        'liability_insurance' => $test->insurance->id,
        'bijak_tracking_code' => 'FRONT-CODE',
        'description' => 'توضیحات بارنامه',
        'transport_contract_id' => $test->contract->id,
        'base_freight_amount' => 100000,
        'weighbridge_amount' => 0,
        'detention_amount' => 5000,
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
