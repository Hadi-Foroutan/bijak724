<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
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

    $repository = app(CompanyDataRepositoryInterface::class);
    $this->shipmentParty = $repository->create($this->company->id, 'shipment_parties', [
        'national_identifier' => '12345678901',
        'is_sender' => true,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'mobile' => '09121234567',
    ]);
    $this->address = $repository->create($this->company->id, 'shipment_party_addresses', [
        'shipment_party_id' => $this->shipmentParty->id,
        'postal_code' => '1234567890',
        'city_code' => $this->city->code,
        'address' => 'تهران، خیابان تست',
    ]);
});

test('it finds a shipment party address by party id and postal code from the request body', function () {
    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'shipment_party_id' => $this->shipmentParty->id,
        'postal_code' => '1234567890',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->address->id)
        ->assertJsonPath('data.shipment_party_id', $this->shipmentParty->id)
        ->assertJsonPath('data.postal_code', '1234567890')
        ->assertJsonPath('data.address', 'تهران، خیابان تست')
        ->assertJsonPath('data.city.name', 'تهران');
});

test('it returns address not found when the postal code does not belong to the shipment party', function () {
    $otherShipmentParty = app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'shipment_parties',
        [
            'national_identifier' => '10987654321',
            'is_sender' => true,
            'is_receiver' => false,
            'status' => 'active',
            'first_name' => 'رضا',
            'last_name' => 'محمدی',
            'mobile' => '09121111111',
        ],
    );

    $this->postJson('/api/user/shipment-parties/addresses/inquiry', [
        'shipment_party_id' => $otherShipmentParty->id,
        'postal_code' => '1234567890',
    ])
        ->assertNotFound()
        ->assertJsonPath('errors.error.0', 'آدرس یافت نشد');
});

test('it validates shipment party id and postal code in the request body', function () {
    $this->postJson('/api/user/shipment-parties/addresses/inquiry')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shipment_party_id', 'postal_code']);
});
