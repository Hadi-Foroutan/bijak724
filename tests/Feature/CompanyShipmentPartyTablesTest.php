<?php

use App\Http\Resources\CompanyCargoResource;
use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $state = State::query()->forceCreate([
        'name' => 'تهران',
        'code' => 11,
    ]);
    City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);

    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '10001',
        'organization_code' => 'ORG-10001',
        'name' => 'شرکت تست طرف‌های حمل',
        'national_code' => '10000000001',
        'city_code' => 1101,
    ]);
});

test('company gets shipment parties and their address tables', function () {
    $partyTable = "company_{$this->company->id}_shipment_parties";
    $addressTable = "company_{$this->company->id}_shipment_party_addresses";

    expect(Schema::hasTable($partyTable))->toBeTrue()
        ->and(Schema::hasColumns($partyTable, [
            'national_identifier',
            'is_sender',
            'is_receiver',
            'status',
            'title',
            'first_name',
            'last_name',
            'full_name',
            'mobile',
            'landline',
            'intermediary_code',
            'transportation_code',
            'email',
            'description',
        ]))->toBeTrue()
        ->and(Schema::hasTable($addressTable))->toBeTrue()
        ->and(Schema::hasColumns($addressTable, [
            'shipment_party_id',
            'postal_code',
            'city_code',
            'address',
            'description',
        ]))->toBeTrue()
        ->and(config('company_tables.sender_receivers'))->toBeNull()
        ->and(config('company_tables.addresses'))->toBeNull();
});

test('shipment party refreshes full name on every update', function () {
    $partyRepository = app(ShipmentPartyRepositoryInterface::class);
    $party = $partyRepository->create($this->company->id, [
        'national_identifier' => '10000000001',
        'is_sender' => true,
        'is_receiver' => false,
        'first_name' => 'علی',
        'last_name' => 'احمدی',
    ]);

    $party->newQuery()->whereKey($party->id)->update(['full_name' => 'نام قدیمی']);

    $updatedParty = $partyRepository->update($this->company->id, $party->id, [
        'mobile' => '09120000000',
    ]);

    expect($updatedParty->full_name)->toBe('علی احمدی');
});

test('shipment party accepts multiple addresses and cascades them on delete', function () {
    $partyRepository = app(ShipmentPartyRepositoryInterface::class);
    $addressRepository = app(ShipmentPartyAddressRepositoryInterface::class);
    $party = $partyRepository->create($this->company->id, [
        'national_identifier' => '10000000001',
        'is_sender' => true,
        'is_receiver' => true,
        'title' => 'شرکت فرستنده و گیرنده',
    ]);

    foreach (['1111111111', '2222222222'] as $postalCode) {
        $addressRepository->create($this->company->id, [
            'shipment_party_id' => $party->id,
            'postal_code' => $postalCode,
            'city_code' => 1101,
            'address' => 'تهران، خیابان تست',
        ]);
    }

    expect($addressRepository->query($this->company->id)->count())->toBe(2);

    $party->delete();

    expect($addressRepository->query($this->company->id)->count())->toBe(0);
});

test('sync command creates missing tables and adds newly configured fields', function () {
    $addressTable = "company_{$this->company->id}_shipment_party_addresses";
    $cargoTable = "company_{$this->company->id}_cargos";
    Schema::drop($addressTable);
    config()->push('company_tables.cargos', [
        'name' => 'description',
        'type' => 'text',
    ]);

    $this->artisan('company-tables:sync', ['--company' => $this->company->id])
        ->assertSuccessful();

    expect(Schema::hasTable($addressTable))->toBeTrue()
        ->and(Schema::hasColumn($cargoTable, 'description'))->toBeTrue();

    $cargo = app(CargoRepositoryInterface::class)->create($this->company->id, [
        'name' => 'محموله تست',
        'description' => 'فیلد تازه',
    ]);

    expect(CompanyCargoResource::make($cargo)->resolve(request()))
        ->toHaveKey('description', 'فیلد تازه');
});
