<?php

use App\Services\Company\CompanyDataService;
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
    ->not->toUse([
        CompanyDataService::class,
        CompanyTableRegistry::class,
    ]);

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
