<?php

use App\Http\Controllers\User\BijakNumberController;
use App\Http\Controllers\User\CanceledReferralController;
use App\Http\Controllers\User\CargoGroupController;
use App\Http\Controllers\User\CompanySettingController;
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

// Dashboard
Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('waybills/daily', 'dailyWaybills')->name('waybills.daily');
    Route::get('waybills/monthly', 'monthlyWaybills')->name('waybills.monthly');
    Route::get('cargos/top', 'topCargos')->name('cargos.top');
    Route::get('drivers/top', 'topDrivers')->name('drivers.top');
});

// Notifications
Route::apiResource('notifications', NotificationController::class)
    ->only(['index', 'show']);

// Company Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [CompanySettingController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/', [CompanySettingController::class, 'update'])->name('update');
});

// Company Users
Route::prefix('{user}/permissions')->name('permissions.')->group(function () {
    Route::get('/', [CompanyUserController::class, 'permissions'])->name('index');
    Route::put('/', [CompanyUserController::class, 'syncPermissions'])->name('update');
});

Route::prefix('users')->name('users.')->group(function () {
    Route::post('{user}', [CompanyUserController::class, 'update'])->name('update');
});
Route::apiResource('users', CompanyUserController::class)
    ->except(['update']);

// Drivers
Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [DriverController::class, 'inquiry'])
        ->name('inquiry');
    Route::post('{driver}', [DriverController::class, 'update'])->name('update');
});
Route::apiResource('drivers', DriverController::class)
    ->except(['update']);
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

// Fleets
Route::prefix('fleets')->name('fleets.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [FleetController::class, 'inquiry'])
        ->name('inquiry');
});
Route::apiResource('fleets', FleetController::class);

// Bijak Numbers
Route::prefix('bijak-numbers')->name('bijak-numbers.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [BijakNumberController::class, 'inquiry'])->name('inquiry');
});
Route::apiResource('bijak-numbers', BijakNumberController::class)
    ->parameters(['bijak-numbers' => 'bijakNumber']);

// Referral Numbers
Route::prefix('referral-numbers')->name('referral-numbers.')->group(function () {
    Route::post('/inquiry', [ReferralNumberController::class, 'inquiry'])->name('inquiry');
});
Route::apiResource('referral-numbers', ReferralNumberController::class)
    ->parameters(['referral-numbers' => 'referralNumber']);

// Canceled Referrals
Route::prefix('canceled-referrals')->name('canceled-referrals.')->group(function () {
    Route::get('/', [CanceledReferralController::class, 'index'])->name('index');
});

// Shipment Parties
Route::prefix('shipment-parties')->name('shipment-parties.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [ShipmentPartyController::class, 'inquiry'])->name('inquiry');
    Route::match(['get', 'post'], '/addresses/inquiry', [ShipmentPartyAddressController::class, 'inquiry'])
        ->name('addresses-inquiry');
});
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

// Waybills
Route::prefix('waybills')->name('waybills.')->group(function () {
    Route::get('/options', [WaybillController::class, 'options'])->name('options');
    Route::patch('{waybill}/cancel', [WaybillController::class, 'cancel'])->name('cancel');
    Route::patch('{waybill}/referral/cancel', [WaybillController::class, 'cancelReferral'])
        ->name('referral.cancel');
});
Route::apiResource('waybills', WaybillController::class);

// Product Owners
Route::apiResource('product-owners', ProductOwnerController::class)
    ->parameters(['product-owners' => 'productOwner']);

// Transport Contracts
Route::prefix('transport-contracts')->name('transport-contracts.')->group(function () {
    Route::get('/options', [TransportContractController::class, 'options'])->name('options');
    Route::get('{transportContract}/users', [TransportContractController::class, 'users'])
        ->name('users');
    Route::put('{transportContract}/users', [TransportContractController::class, 'syncUsers'])
        ->name('syncUsers');
});
Route::apiResource('transport-contracts', TransportContractController::class)
    ->parameters(['transport-contracts' => 'transportContract']);

// Insurances
Route::prefix('insurances')->name('insurances.')->group(function () {
    Route::match(['get', 'post'], '/inquiry', [InsuranceController::class, 'inquiry'])->name('inquiry');
});
Route::apiResource('insurances', InsuranceController::class);
Route::apiResource('insurances.tariffs', InsuranceTariffController::class)
    ->parameters(['tariffs' => 'tariff']);

// Cargo Groups
Route::prefix('cargo-groups')->group(function () {
    Route::name('cargo-groups.')->group(function () {
        Route::get('/', [CargoGroupController::class, 'index'])->name('index');
        Route::get('{cargoGroup}', [CargoGroupController::class, 'show'])->name('show');
    });

    Route::put('{cargoGroup}/cargos', [CargoGroupController::class, 'syncCargos'])
        ->name('cargo-groups-cargos.update');
});
