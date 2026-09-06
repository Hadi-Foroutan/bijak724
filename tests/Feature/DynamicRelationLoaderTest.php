<?php

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\City;
use App\Models\Company\Driver as CompanyDriver;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use App\Models\DriverLicenseType;
use App\Models\State;
use App\Services\Company\DynamicRelationLoader;
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

    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $repository = app(CompanyDataRepositoryInterface::class);
    $repository->create(42, 'drivers', ['license_type' => $licenseType->id]);
    $drivers = $repository->search(42, 'drivers', [
        'paginate' => true,
        'itemsPerPage' => 10,
    ]);

    app(DynamicRelationLoader::class)->load($drivers);

    expect($drivers->getCollection()->first())->toBeInstanceOf(CompanyDriver::class)
        ->and($drivers->getCollection()->first()->relationLoaded('licenseType'))->toBeTrue()
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

    $repository = app(CompanyDataRepositoryInterface::class);
    $party = $repository->create(42, 'shipment_parties', ['title' => 'فرستنده تست']);
    $address = $repository->create(42, 'shipment_party_addresses', [
        'shipment_party_id' => $party->id,
        'city_code' => $city->code,
        'address' => 'تهران، خیابان تست',
    ]);

    app(DynamicRelationLoader::class)->load($party);

    expect($party)->toBeInstanceOf(ShipmentParty::class)
        ->and($address)->toBeInstanceOf(ShipmentPartyAddress::class)
        ->and($party->relationLoaded('addresses'))->toBeTrue()
        ->and($party->addresses)->toHaveCount(1)
        ->and($party->addresses->first()->relationLoaded('city'))->toBeTrue()
        ->and($party->addresses->first()->city->is($city))->toBeTrue()
        ->and($party->addresses()->first()->is($address))->toBeTrue();

    app(DynamicRelationLoader::class)->load($address);

    expect($address->relationLoaded('shipmentParty'))->toBeTrue()
        ->and($address->shipmentParty->is($party))->toBeTrue()
        ->and($address->city->is($city))->toBeTrue()
        ->and($address->shipmentParty()->first()->is($party))->toBeTrue();
});
