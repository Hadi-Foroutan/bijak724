<?php

use App\Services\Company\CompanyTableRegistry;

arch('dynamic domain services do not access the generic dynamic table infrastructure')
    ->expect([
        'App\Services\Company\Cargo',
        'App\Services\Company\Driver',
        'App\Services\Company\Fleet',
        'App\Services\Company\ProductOwner',
        'App\Services\Company\ShipmentParty',
        'App\Services\Company\Waybill',
    ])
    ->not->toUse(CompanyTableRegistry::class);

test('generic company data service and repository are not available', function () {
    $applicationPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR;

    expect(class_exists('App\\Services\\Company\\CompanyDataService'))->toBeFalse()
        ->and(interface_exists('App\\Interfaces\\CompanyDataRepositoryInterface'))->toBeFalse()
        ->and(file_exists($applicationPath.'Services/Company/CompanyCrudService.php'))->toBeFalse()
        ->and(file_exists($applicationPath.'Repositories/Company/CompanyModelRepository.php'))->toBeFalse()
        ->and(file_exists($applicationPath.'Interfaces/Company/CompanyModelRepositoryInterface.php'))->toBeFalse()
        ->and(file_exists($applicationPath.'Services/Company/DynamicRelationLoader.php'))->toBeFalse();
});

arch('dynamic domain controllers do not query models directly')
    ->expect([
        'App\Http\Controllers\User\Cargo',
        'App\Http\Controllers\User\Driver',
        'App\Http\Controllers\User\Fleet',
        'App\Http\Controllers\User\ProductOwner',
        'App\Http\Controllers\User\ShipmentParty',
        'App\Http\Controllers\User\Waybill',
    ])
    ->not->toUse('App\Models');
