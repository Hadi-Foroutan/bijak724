<?php

use App\Http\Resources\CompanyCargoResource;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        'is_sender' => true,
        'is_receiver' => true,
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

    $cargo = app(CompanyDataRepositoryInterface::class)->create($this->company->id, 'cargos', [
        'name' => 'محموله تست',
        'description' => 'فیلد تازه',
    ]);

    expect(CompanyCargoResource::make($cargo)->resolve(request()))
        ->toHaveKey('description', 'فیلد تازه');
});

test('migration converts legacy shipment party types to boolean roles', function () {
    $tableName = 'company_999_shipment_parties';
    Schema::create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->enum('type', ['sender', 'receiver', 'both']);
    });
    DB::table($tableName)->insert([
        ['id' => 1, 'type' => 'sender'],
        ['id' => 2, 'type' => 'receiver'],
        ['id' => 3, 'type' => 'both'],
    ]);

    $migration = require database_path('migrations/2026_09_05_124845_replace_shipment_party_type_with_sender_receiver_flags.php');
    $migration->up();

    $sender = DB::table($tableName)->where('id', 1)->first();
    $receiver = DB::table($tableName)->where('id', 2)->first();
    $both = DB::table($tableName)->where('id', 3)->first();

    expect(Schema::hasColumn($tableName, 'type'))->toBeFalse()
        ->and($sender->is_sender)->toBe(1)
        ->and($sender->is_receiver)->toBe(0)
        ->and($receiver->is_sender)->toBe(0)
        ->and($receiver->is_receiver)->toBe(1)
        ->and($both->is_sender)->toBe(1)
        ->and($both->is_receiver)->toBe(1);
});
