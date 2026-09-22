<?php

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\DriverAccountRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\WaybillCargoRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Cargo;
use App\Models\Company\Driver;
use App\Models\Company\DriverAccount;
use App\Models\Company\Fleet;
use App\Models\Company\ProductOwner;
use App\Models\Company\ReferralNumber;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use App\Models\Company\Waybill;
use App\Models\Company\WaybillCargo;
use App\Models\DynamicModel;
use App\Services\Company\CompanyTableRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('company_42_cargos', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->string('name');
        $table->string('national_code');
        $table->timestamps();
    });
});

test('dedicated repository queries and creates records through its own model', function () {
    $repository = app(CargoRepositoryInterface::class);

    $cargo = $repository->create(42, [
        'name' => 'بار تست',
        'national_code' => 'CARGO-42',
    ]);

    expect($cargo)->toBeInstanceOf(Cargo::class)
        ->and($cargo)->toBeInstanceOf(DynamicModel::class)
        ->and($repository->query(42)->getModel()->getTable())->toBe('company_42_cargos')
        ->and($repository->query(42)->value('national_code'))->toBe('CARGO-42');
});

test('dedicated repository applies dynamic table search configuration', function () {
    $repository = app(CargoRepositoryInterface::class);

    $repository->create(42, ['name' => 'بار اول', 'national_code' => 'CARGO-1']);
    $repository->create(42, ['name' => 'بار دوم', 'national_code' => 'CARGO-2']);

    $result = $repository->search(42, [
        'eq-national_code' => 'CARGO-2',
        'paginate' => true,
    ]);

    expect($result->total())->toBe(1)
        ->and($result->first()->national_code)->toBe('CARGO-2');
});

test('every dynamic table repository declares its dedicated model', function () {
    $repositories = [
        CargoRepositoryInterface::class => [Cargo::class, 'cargos'],
        DriverRepositoryInterface::class => [Driver::class, 'drivers'],
        DriverAccountRepositoryInterface::class => [DriverAccount::class, 'driver_accounts'],
        FleetRepositoryInterface::class => [Fleet::class, 'fleets'],
        ProductOwnerRepositoryInterface::class => [ProductOwner::class, 'product_owner'],
        ReferralNumberRepositoryInterface::class => [ReferralNumber::class, 'referral_numbers'],
        ShipmentPartyRepositoryInterface::class => [ShipmentParty::class, 'shipment_parties'],
        ShipmentPartyAddressRepositoryInterface::class => [ShipmentPartyAddress::class, 'shipment_party_addresses'],
        WaybillRepositoryInterface::class => [Waybill::class, 'waybills'],
        WaybillCargoRepositoryInterface::class => [WaybillCargo::class, 'waybill_cargos'],
    ];

    foreach ($repositories as $repositoryInterface => [$modelClass, $tableKey]) {
        $repository = app($repositoryInterface);
        $model = $repository->query(42)->getModel();
        $constructorTypes = collect((new ReflectionClass($repository))->getConstructor()?->getParameters())
            ->map(fn (ReflectionParameter $parameter): ?string => $parameter->getType()?->getName());

        expect($model)->toBeInstanceOf($modelClass)
            ->and($model->getTable())->toBe("company_42_{$tableKey}")
            ->and($constructorTypes)->toContain($modelClass)
            ->and((new ReflectionClass($repository))->getParentClass())->toBeFalse();
    }

    expect((new ReflectionClass(DynamicModel::class))->isAbstract())->toBeTrue()
        ->and(method_exists(CompanyTableRegistry::class, 'query'))->toBeFalse();
});
