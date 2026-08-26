<?php

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company as CompanyModel;
use App\Models\Company\Cargo;
use App\Models\Company\Driver;
use App\Models\Company\Fleet;
use App\Models\Company\ProductOwner;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use App\Models\Company\Waybill;
use App\Repositories\Company\CargoRepository;
use App\Repositories\Company\DriverRepository;
use App\Repositories\Company\FleetRepository;
use App\Repositories\Company\ProductOwnerRepository;
use App\Repositories\Company\ShipmentPartyAddressRepository;
use App\Repositories\Company\ShipmentPartyRepository;
use App\Repositories\Company\WaybillRepository;
use App\Services\Company\Cargo\CargoService;
use App\Services\Company\Driver\DriverService;
use App\Services\Company\Fleet\FleetService;
use App\Services\Company\ProductOwner\ProductOwnerService;
use App\Services\Company\ShipmentParty\ShipmentPartyAddressService;
use App\Services\Company\ShipmentParty\ShipmentPartyService;
use App\Services\Company\Waybill\WaybillService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('each company repository interface resolves its own repository and model', function () {
    $company = CompanyModel::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '80001',
        'organization_code' => 'ORG-TYPED-REPOSITORIES',
        'name' => 'شرکت ریپازیتوری تایپ‌شده',
        'national_code' => '80000000001',
        'city_code' => '1101',
    ]);

    $bindings = [
        DriverRepositoryInterface::class => [DriverRepository::class, Driver::class],
        FleetRepositoryInterface::class => [FleetRepository::class, Fleet::class],
        ShipmentPartyRepositoryInterface::class => [ShipmentPartyRepository::class, ShipmentParty::class],
        ShipmentPartyAddressRepositoryInterface::class => [ShipmentPartyAddressRepository::class, ShipmentPartyAddress::class],
        WaybillRepositoryInterface::class => [WaybillRepository::class, Waybill::class],
        CargoRepositoryInterface::class => [CargoRepository::class, Cargo::class],
        ProductOwnerRepositoryInterface::class => [ProductOwnerRepository::class, ProductOwner::class],
    ];

    foreach ($bindings as $interface => [$repositoryClass, $modelClass]) {
        $repository = app($interface);

        expect($repository)->toBeInstanceOf($repositoryClass)
            ->and($repository->query($company->id)->getModel())->toBeInstanceOf($modelClass);
    }
});

test('company services depend on their dedicated repository interfaces', function () {
    $services = [
        DriverService::class => DriverRepositoryInterface::class,
        FleetService::class => FleetRepositoryInterface::class,
        ShipmentPartyService::class => ShipmentPartyRepositoryInterface::class,
        ShipmentPartyAddressService::class => ShipmentPartyAddressRepositoryInterface::class,
        WaybillService::class => WaybillRepositoryInterface::class,
        CargoService::class => CargoRepositoryInterface::class,
        ProductOwnerService::class => ProductOwnerRepositoryInterface::class,
    ];

    foreach ($services as $serviceClass => $repositoryInterface) {
        $constructor = (new ReflectionClass($serviceClass))->getConstructor();
        $parameterTypes = collect($constructor?->getParameters())
            ->map(fn (ReflectionParameter $parameter): ?string => $parameter->getType()?->getName());

        expect($parameterTypes)->toContain($repositoryInterface);
    }
});
