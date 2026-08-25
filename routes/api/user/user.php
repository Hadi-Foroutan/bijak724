<?php

use App\Http\Controllers\User\Dashboard\DashboardController;
use App\Http\Controllers\User\Driver\DriverController;
use App\Http\Controllers\User\Fleet\FleetController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::apiResource('drivers', DriverController::class);
Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/inquiry/{nationalCode}', [DriverController::class, 'inquiry'])
        ->name('inquiry');
});

Route::apiResource('fleets', FleetController::class);
Route::prefix('fleets')->name('fleets.')->group(function () {
    Route::get('/inquiry/{smartCardNumber}', [FleetController::class, 'inquiry'])
        ->name('inquiry');
});
