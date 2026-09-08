<?php

use App\Enums\TransportContractItemName;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Cargo;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\Packaging;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
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
    $this->fleet = $repository->create($this->company->id, 'fleets', [
        'status' => 'active',
        'ownership_type' => 'owned',
        'plate_first_number' => '12',
        'plate_second_letter' => 'ب',
        'plate_third_number' => '345',
        'plate_fourth_number' => '67',
        'has_violation' => false,
    ]);
});

test('it creates a complete waybill with snapshots cargos and calculated contract amounts', function () {
    $response = $this->postJson('/api/user/waybills', completeWaybillPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.receiver_last_name', 'گیرنده')
        ->assertJsonPath('data.driver1_national_code', '1234567890')
        ->assertJsonPath('data.driver2_phone', '09122222222')
        ->assertJsonPath('data.advance_freight_amount', 0)
        ->assertJsonPath('data.weighbridge_amount', 5000)
        ->assertJsonPath('data.commission_amount', 10000)
        ->assertJsonPath('data.insurance_amount', 2000)
        ->assertJsonPath('data.insurance_tax_amount', 10000)
        ->assertJsonPath('data.driver_receivable_amount', 22000)
        ->assertJsonPath('data.payable_amount', 132000)
        ->assertJsonCount(1, 'data.cargos')
        ->assertJsonPath('data.cargos.0.origin_weight', 1250.5);

    $waybillId = $response->json('data.id');
    $cargoItemId = $response->json('data.cargos.0.id');
    $this->sender->update(['first_name' => 'نام جدید']);
    $this->firstDriver->update(['first_name' => 'راننده جدید']);

    $this->getJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful()
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.driver1_first_name', 'حسین')
        ->assertJsonPath('data.sender.first_name', 'نام جدید')
        ->assertJsonPath('data.first_driver.first_name', 'راننده جدید');

    $updatePayload = completeWaybillPayload($this);
    $updatePayload['is_fixed'] = true;
    $updatePayload['payable_amount'] = 999999;

    $this->putJson("/api/user/waybills/{$waybillId}", $updatePayload)
        ->assertSuccessful()
        ->assertJsonPath('data.payable_amount', 999999)
        ->assertJsonPath('data.sender_first_name', 'علی')
        ->assertJsonPath('data.cargos.0.id', $cargoItemId);
});

test('it stores an incomplete waybill', function () {
    $this->postJson('/api/user/waybills', ['is_incomplete' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
        ->assertJsonCount(0, 'data.cargos');
});

test('it validates all required complete waybill data and company references', function () {
    $this->postJson('/api/user/waybills', ['is_incomplete' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sender_id', 'receiver_id', 'driver1_id', 'fleet_id', 'transport_contract_id',
            'base_freight_amount', 'cargos',
        ]);

    $payload = completeWaybillPayload($this);
    $payload['sender_id'] = $this->receiver->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sender_id');

    $otherCompany = Company::factory()->create();
    $payload = completeWaybillPayload($this);
    $payload['transport_contract_id'] = $otherCompany->transportContracts()->sole()->id;

    $this->postJson('/api/user/waybills', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transport_contract_id');
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
            'sender_id', 'receiver_id', 'driver1_id', 'fleet_id',
            'transport_contract_id', 'base_freight_amount', 'cargos',
        ]);
});

/** @return array<string, mixed> */
function completeWaybillPayload(object $test): array
{
    return [
        'is_incomplete' => false,
        'sender_id' => $test->sender->id,
        'receiver_id' => $test->receiver->id,
        'driver1_id' => $test->firstDriver->id,
        'driver2_id' => $test->secondDriver->id,
        'fleet_id' => $test->fleet->id,
        'referral_weight' => 1250.5,
        'quantity' => 10,
        'loading_started_at' => '2026-09-07 08:00:00',
        'loading_ended_at' => '2026-09-07 10:00:00',
        'referral_number' => 'REF-1',
        'bijak_number' => 'BIJAK-1',
        'serial_number' => 'SERIAL-1',
        'issued_at' => '2026-09-07 11:00:00',
        'liability_insurance' => 'INS-1',
        'bijak_tracking_code' => 'TRACK-1',
        'transport_contract_id' => $test->contract->id,
        'base_freight_amount' => 100000,
        'weighbridge_amount' => 0,
        'detention_amount' => 5000,
        'freight_at_origin' => true,
        'is_fixed' => false,
        'cargos' => [[
            'cargo_id' => $test->cargo->id,
            'packaging_id' => $test->packaging->id,
            'title' => 'محموله گندم',
            'description' => 'توضیحات محموله',
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
