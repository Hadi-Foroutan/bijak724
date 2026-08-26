<?php

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\Company\Cargo as CompanyCargo;
use App\Models\Company\Driver as CompanyDriver;
use App\Models\Company\Fleet as CompanyFleet;
use App\Models\Company\ProductOwner as CompanyProductOwner;
use App\Models\Company\ShipmentParty as CompanyShipmentParty;
use App\Models\Company\ShipmentPartyAddress as CompanyShipmentPartyAddress;
use App\Models\Company\Waybill as CompanyWaybill;
use App\Models\DynamicModel;
use App\Services\Company\CompanyDataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('company_42_waybills', function (Blueprint $table): void {
        $table->id();
        $table->string('tracking_code');
        $table->timestamps();
    });
});

test('repository contract queries and creates company scoped records', function () {
    $repository = app(CompanyDataRepositoryInterface::class);

    $waybill = $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-001',
    ]);

    expect($waybill)->toBeInstanceOf(CompanyWaybill::class)
        ->and($waybill)->toBeInstanceOf(DynamicModel::class);
    expect($repository->table(42, 'waybills'))->toBe('company_42_waybills');
    expect($repository->query(42, 'waybills')->value('tracking_code'))->toBe('WB-001');
    expect($repository->query(42, 'waybills', 'waybill')->where('waybill.id', $waybill->id)->exists())->toBeTrue();
});

test('company data service passes company and table arguments in the correct order', function () {
    $repository = app(CompanyDataRepositoryInterface::class);

    $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-001',
    ]);
    $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-002',
    ]);

    $result = app(CompanyDataService::class)->list(42, 'waybills', [
        'tracking_code' => 'WB-002',
    ]);

    expect($result->total())->toBe(1);
    expect($result->first()->tracking_code)->toBe('WB-002');
});

test('repository resolves dedicated models by company table convention', function () {
    $repository = app(CompanyDataRepositoryInterface::class);
    $models = [
        'waybills' => CompanyWaybill::class,
        'drivers' => CompanyDriver::class,
        'fleets' => CompanyFleet::class,
        'shipment_parties' => CompanyShipmentParty::class,
        'shipment_party_addresses' => CompanyShipmentPartyAddress::class,
        'cargos' => CompanyCargo::class,
        'product_owner' => CompanyProductOwner::class,
    ];

    foreach ($models as $tableKey => $modelClass) {
        $model = $repository->query(42, $tableKey)->getModel();

        expect($model)->toBeInstanceOf($modelClass)
            ->and($model->getTable())->toBe("company_42_{$tableKey}");
    }

    $fallbackModel = $repository->query(42, 'custom_records')->getModel();

    expect($fallbackModel)->toBeInstanceOf(DynamicModel::class)
        ->and($fallbackModel::class)->toBe(DynamicModel::class)
        ->and($fallbackModel->getTable())->toBe('company_42_custom_records');
});
