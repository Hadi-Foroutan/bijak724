<?php

use App\Http\Controllers\User\Cargo\CargoController;
use App\Http\Controllers\User\CargoGroupController;
use App\Http\Controllers\User\CompanyUserController;
use App\Http\Controllers\User\Dashboard\DashboardController;
use App\Http\Controllers\User\Driver\DriverAccountController;
use App\Http\Controllers\User\Driver\DriverController;
use App\Http\Controllers\User\Fleet\FleetController;
use App\Http\Controllers\User\InsuranceController;
use App\Http\Controllers\User\InsuranceTariffController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ProductOwner\ProductOwnerController;
use App\Http\Controllers\User\ReferralNumberController;
use App\Http\Controllers\User\ShipmentParty\ShipmentPartyAddressController;
use App\Http\Controllers\User\ShipmentParty\ShipmentPartyController;
use App\Http\Controllers\User\TransportContractController;
use App\Http\Controllers\User\Waybill\WaybillController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('waybills/daily', 'dailyWaybills')->name('waybills.daily');
    Route::get('waybills/monthly', 'monthlyWaybills')->name('waybills.monthly');
    Route::get('cargos/top', 'topCargos')->name('cargos.top');
    Route::get('drivers/top', 'topDrivers')->name('drivers.top');
});

Route::apiResource('notifications', NotificationController::class)
    ->only(['index', 'show']);

Route::get('{user}/permissions', [CompanyUserController::class, 'permissions'])
    ->name('permissions.index');
Route::put('{user}/permissions', [CompanyUserController::class, 'syncPermissions'])
    ->name('permissions.update');

Route::apiResource('users', CompanyUserController::class)
    ->except(['update']);
Route::post('users/{user}', [CompanyUserController::class, 'update'])
    ->name('users.update');

Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [DriverController::class, 'inquiry'])
        ->name('inquiry');
});
Route::apiResource('drivers', DriverController::class)
    ->except(['update']);
Route::post('drivers/{driver}', [DriverController::class, 'update'])
    ->name('drivers.update');
Route::apiResource('drivers.accounts', DriverAccountController::class)
    ->names([
        'index' => 'drivers.accounts.index',
        'store' => 'drivers.accounts.store',
        'show' => 'drivers.accounts.show',
        'update' => 'drivers.accounts.update',
        'destroy' => 'drivers.accounts.destroy',
    ])
    ->parameters([
        'drivers' => 'driver',
        'accounts' => 'account',
    ]);

Route::prefix('fleets')->name('fleets.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [FleetController::class, 'inquiry'])
        ->name('inquiry');
});
Route::apiResource('fleets', FleetController::class);

Route::get('referral-numbers/inquiry', [ReferralNumberController::class, 'inquiry'])
    ->name('referral-numbers.inquiry');
Route::apiResource('referral-numbers', ReferralNumberController::class)
    ->parameters(['referral-numbers' => 'referralNumber']);

Route::prefix('shipment-parties')->name('shipment-parties.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [ShipmentPartyController::class, 'inquiry'])->name('inquiry');
});
Route::apiResource('shipment-parties', ShipmentPartyController::class)
    ->parameters(['shipment-parties' => 'shipmentParty']);

Route::prefix('shipment-parties')->name('shipment-parties.')->group(function () {
    Route::match(['get', 'post'], '/addresses/inquiry', [ShipmentPartyAddressController::class, 'inquiry'])->name('addresses-inquiry');
    Route::match(['get', 'post'], '/inquiry', [ShipmentPartyController::class, 'inquiry'])->name('inquiry');
});
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

// Route::get('waybills/options', [WaybillController::class, 'options'])->name('waybills.options');
Route::apiResource('waybills', WaybillController::class);
// Route::apiResource('cargos', CargoController::class);
Route::apiResource('product-owners', ProductOwnerController::class)
    ->parameters(['product-owners' => 'productOwner']);

Route::get('transport-contracts/options', [TransportContractController::class, 'options'])
    ->name('transport-contracts.options');
Route::apiResource('transport-contracts', TransportContractController::class)
    ->parameters(['transport-contracts' => 'transportContract']);

Route::prefix('insurances')->name('insurances.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [InsuranceController::class, 'inquiry'])->name('inquiry');
});
Route::apiResource('insurances', InsuranceController::class);
Route::apiResource('insurances.tariffs', InsuranceTariffController::class)
    ->parameters(['tariffs' => 'tariff']);

Route::get('cargo-groups', [CargoGroupController::class, 'index'])->name('cargo-groups.index');
Route::get('cargo-groups/{cargoGroup}', [CargoGroupController::class, 'show'])->name('cargo-groups.show');
Route::put('cargo-groups/{cargoGroup}/cargos', [CargoGroupController::class, 'syncCargos'])
    ->name('cargo-groups-cargos.update');
