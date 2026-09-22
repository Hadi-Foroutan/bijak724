<?php

use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\City;
use App\Models\Company\Driver as CompanyDriver;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use App\Models\DriverLicenseType;
use App\Models\State;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('it loads configured static relations for dynamic records and paginators', function () {
    Schema::create('company_42_drivers', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->unsignedBigInteger('license_type');
        $table->timestamps();
    });
    Schema::create('company_42_driver_accounts', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->unsignedBigInteger('driver_id')->index();
        $table->string('sheba_number', 34);
        $table->string('bank_name');
        $table->string('owner_name');
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });

    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $repository = app(DriverRepositoryInterface::class);
    $repository->create(42, ['license_type' => $licenseType->id]);
    $drivers = $repository->search(42, [
        'paginate' => true,
        'itemsPerPage' => 10,
    ]);

    expect($drivers->getCollection()->first())->toBeInstanceOf(CompanyDriver::class)
        ->and($drivers->getCollection()->first()->relationLoaded('licenseType'))->toBeTrue()
        ->and($drivers->getCollection()->first()->relationLoaded('defaultAccount'))->toBeTrue()
        ->and($drivers->getCollection()->first()->defaultAccount)->toBeNull()
        ->and($drivers->getCollection()->first()->licenseType->is($licenseType))->toBeTrue();
});

test('it loads dynamic has many relations with nested static relations', function () {
    $state = State::query()->forceCreate([
        'name' => 'تهران',
        'code' => 11,
    ]);
    $city = City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);

    Schema::create('company_42_shipment_parties', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->string('title')->nullable();
        $table->timestamps();
    });
    Schema::create('company_42_shipment_party_addresses', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->unsignedBigInteger('shipment_party_id');
        $table->unsignedInteger('city_code');
        $table->text('address');
        $table->timestamps();
    });

    $party = app(ShipmentPartyRepositoryInterface::class)->create(42, ['title' => 'فرستنده تست']);
    $address = app(ShipmentPartyAddressRepositoryInterface::class)->create(42, [
        'shipment_party_id' => $party->id,
        'city_code' => $city->code,
        'address' => 'تهران، خیابان تست',
    ]);

    $party = app(ShipmentPartyRepositoryInterface::class)->findOrFail(42, $party->id);

    expect($party)->toBeInstanceOf(ShipmentParty::class)
        ->and($address)->toBeInstanceOf(ShipmentPartyAddress::class)
        ->and($party->relationLoaded('addresses'))->toBeTrue()
        ->and($party->addresses)->toHaveCount(1)
        ->and($party->addresses->first()->relationLoaded('city'))->toBeTrue()
        ->and($party->addresses->first()->city->is($city))->toBeTrue()
        ->and($party->addresses()->first()->is($address))->toBeTrue();

    expect($address->relationLoaded('shipmentParty'))->toBeTrue()
        ->and($address->shipmentParty->is($party))->toBeTrue()
        ->and($address->city->is($city))->toBeTrue()
        ->and($address->shipmentParty()->first()->is($party))->toBeTrue();
});
