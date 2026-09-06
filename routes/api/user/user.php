<?php

use App\Http\Controllers\User\Cargo\CargoController;
use App\Http\Controllers\User\CompanyUserController;
use App\Http\Controllers\User\Dashboard\DashboardController;
use App\Http\Controllers\User\Driver\DriverController;
use App\Http\Controllers\User\Fleet\FleetController;
use App\Http\Controllers\User\ProductOwner\ProductOwnerController;
use App\Http\Controllers\User\ShipmentParty\ShipmentPartyAddressController;
use App\Http\Controllers\User\ShipmentParty\ShipmentPartyController;
use App\Http\Controllers\User\Waybill\WaybillController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('users/{user}/permissions', [CompanyUserController::class, 'permissions'])
    ->name('permissions.index');
Route::put('users/{user}/permissions', [CompanyUserController::class, 'syncPermissions'])
    ->name('permissions.update');

Route::apiResource('users', CompanyUserController::class)
    ->except(['update']);
Route::post('users/{user}', [CompanyUserController::class, 'update'])
    ->name('users.update');

Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::match(['get', 'post'], '/inquiry/{nationalCode}', [DriverController::class, 'inquiry'])
        ->name('inquiry');
});
Route::apiResource('drivers', DriverController::class);

Route::prefix('fleets')->name('fleets.')->group(function () {
    Route::match(['get', 'post'], '/inquiry/{smartCardNumber}', [FleetController::class, 'inquiry'])
        ->name('inquiry');
});
Route::apiResource('fleets', FleetController::class);

Route::apiResource('shipment-parties', ShipmentPartyController::class)
    ->parameters(['shipment-parties' => 'shipmentParty']);
Route::apiResource('shipment-parties.addresses', ShipmentPartyAddressController::class)
    ->names([
        'index' => 'addresses.index',
        'store' => 'addresses.store',
        'show' => 'addresses.show',
        'update' => 'addresses.update',
        'destroy' => 'addresses.destroy',
    ])
    ->parameters([
        'shipment-parties' => 'shipmentParty',
        'addresses' => 'address',
    ]);

Route::apiResource('waybills', WaybillController::class);
Route::apiResource('cargos', CargoController::class);
Route::apiResource('product-owners', ProductOwnerController::class)
    ->parameters(['product-owners' => 'productOwner']);
