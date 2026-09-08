<?php

use App\Http\Controllers\User\GeneralOptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('general')->name('general.')->controller(GeneralOptionController::class)->group(function (): void {
    Route::get('cargos', 'cargos')->name('cargos');
    Route::get('packaging', 'packaging')->name('packaging');
    Route::get('fleet-types', 'fleetTypes')->name('fleet-types');
    Route::get('fleet-systems', 'fleetSystems')->name('fleet-systems');
    Route::get('states', 'states')->name('states');
    Route::get('cities', 'cities')->name('cities');
    Route::get('insurance-companies', 'insuranceCompanies')->name('insurance-companies');
});
