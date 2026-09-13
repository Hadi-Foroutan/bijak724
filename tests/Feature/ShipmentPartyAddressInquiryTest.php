<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'shipment-party-address-inquiry',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);

    $state = State::query()->forceCreate(['name' => 'تهران', 'code' => 11]);
    $this->city = City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);

    $this->shipmentPartyRepository = app(ShipmentPartyRepositoryInterface::class);
    $this->addressRepository = app(ShipmentPartyAddressRepositoryInterface::class);
    $this->shipmentParty = $this->shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '12345678901',
        'is_sender' => true,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'mobile' => '09121234567',
    ]);
    $this->address = $this->addressRepository->create($this->company->id, [
        'shipment_party_id' => $this->shipmentParty->id,
        'postal_code' => '1234567890',
        'city_code' => $this->city->code,
        'address' => 'تهران، خیابان تست',
    ]);
    $this->secondAddress = $this->addressRepository->create($this->company->id, [
        'shipment_party_id' => $this->shipmentParty->id,
        'postal_code' => '1111111111',
        'city_code' => $this->city->code,
        'address' => 'تهران، خیابان دوم',
    ]);
});

test('it finds the sender by postal code and returns all party addresses', function () {
    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '1234567890',
        'type' => 'sender',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->shipmentParty->id)
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonCount(2, 'data.addresses')
        ->assertJsonPath('data.addresses.0.postal_code', '1234567890')
        ->assertJsonPath('data.addresses.0.city.name', 'تهران')
        ->assertJsonPath('data.addresses.1.postal_code', '1111111111');
});

test('it reports an inactive shipment party found by postal code', function () {
    $this->shipmentParty->update(['status' => 'inactive']);

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '1234567890',
        'type' => 'sender',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'فرستنده غیرفعال است.')
        ->assertJsonPath('errors.status.0', 'فرستنده غیرفعال است.');

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '1234567890',
        'type' => 'receiver',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'گیرنده غیرفعال است.');
});

test('it selects the shipment party matching the requested role', function () {
    $sender = $this->shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '10000000001',
        'is_sender' => true,
        'is_receiver' => false,
        'status' => 'active',
        'first_name' => 'فرستنده',
    ]);
    $receiver = $this->shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '10000000002',
        'is_sender' => false,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'گیرنده',
    ]);

    foreach ([$sender, $receiver] as $shipmentParty) {
        $this->addressRepository->create($this->company->id, [
            'shipment_party_id' => $shipmentParty->id,
            'postal_code' => '2222222222',
            'city_code' => $this->city->code,
            'address' => 'تهران، آدرس مشترک',
        ]);
    }

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '2222222222',
        'type' => 'sender',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $sender->id)
        ->assertJsonPath('data.is_sender', true);

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '2222222222',
        'type' => 'receiver',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $receiver->id)
        ->assertJsonPath('data.is_receiver', true);
});

test('it returns address not found when postal code does not belong to the requested role', function () {
    $receiver = $this->shipmentPartyRepository->create($this->company->id, [
        'national_identifier' => '10987654321',
        'is_sender' => false,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'رضا',
        'last_name' => 'محمدی',
        'mobile' => '09121111111',
    ]);
    $this->addressRepository->create($this->company->id, [
        'shipment_party_id' => $receiver->id,
        'postal_code' => '3333333333',
        'city_code' => $this->city->code,
        'address' => 'تهران، آدرس گیرنده',
    ]);

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '3333333333',
        'type' => 'sender',
    ])
        ->assertNotFound()
        ->assertJsonPath('errors.error.0', 'آدرس یافت نشد');
});

test('it validates postal code and type in the request body', function () {
    $this->postJson('/api/user/shipment-parties/addresses/inquiry')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['postal_code', 'type']);

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'postal_code' => '1234567890',
        'type' => 'reciever',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});
