<?php

use App\Enums\ShipmentPartyType;
use App\Interfaces\CompanyDataRepositoryInterface;
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
            'type',
            'status',
            'title',
            'first_name',
            'last_name',
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

test('shipment party accepts multiple addresses and cascades them on delete', function () {
    $repository = app(CompanyDataRepositoryInterface::class);
    $party = $repository->create($this->company->id, 'shipment_parties', [
        'national_identifier' => '10000000001',
        'type' => ShipmentPartyType::Both->value,
        'title' => 'شرکت فرستنده و گیرنده',
    ]);

    foreach (['1111111111', '2222222222'] as $postalCode) {
        $repository->create($this->company->id, 'shipment_party_addresses', [
            'shipment_party_id' => $party->id,
            'postal_code' => $postalCode,
            'city_code' => 1101,
            'address' => 'تهران، خیابان تست',
        ]);
    }

    expect($repository->query($this->company->id, 'shipment_party_addresses')->count())->toBe(2);

    $party->delete();

    expect($repository->query($this->company->id, 'shipment_party_addresses')->count())->toBe(0);
});

test('sync command recreates only missing company tables', function () {
    $addressTable = "company_{$this->company->id}_shipment_party_addresses";
    Schema::drop($addressTable);

    $this->artisan('company-tables:sync', ['--company' => $this->company->id])
        ->assertSuccessful();

    expect(Schema::hasTable($addressTable))->toBeTrue();
});
