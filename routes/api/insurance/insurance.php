<?php

use App\Http\Controllers\Insurance\InsuranceCompanyController;
use App\Http\Controllers\Insurance\InsuranceWaybillController;
use Illuminate\Support\Facades\Route;

// The insurance panel (the insurers' agents): the companies and the waybills their contracts insured, by the user's level.
Route::get('companies', [InsuranceCompanyController::class, 'index'])->name('companies.index');
Route::get('companies/{company}/waybills', [InsuranceWaybillController::class, 'index'])->name('companies.waybills.index');
Route::get('companies/{company}/waybills/{waybill}', [InsuranceWaybillController::class, 'show'])->name('companies.waybills.show');
