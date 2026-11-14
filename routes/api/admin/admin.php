<?php

use App\Http\Controllers\Admin\City\CityController;
use App\Http\Controllers\Admin\Company\CompanyController;
use App\Http\Controllers\Admin\Company\CompanySupportTokenController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\InsuranceAgency\InsuranceAgencyController;
use App\Http\Controllers\Admin\InsuranceAgency\InsuranceAgencySupportTokenController;
use App\Http\Controllers\Admin\InsuranceAgency\InsuranceCompanyController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\Permission\PermissionController;
use App\Http\Controllers\Admin\Permission\RoleController;
use App\Http\Controllers\Admin\Report\ReportFieldController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\WaybillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard')->name('dashboard.')->controller(DashboardController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    //    Route::get('companies/top', 'topCompanies')->name('companies-top');
    Route::get('companies/top/seven', 'topSevenCompanies')->name('companies-top-seven');
    Route::get('companies/top/monthly', 'currentMonthTopCompanies')->name('companies-top-monthly');
    Route::get('waybills/monthly', 'monthlyWaybills')->name('waybills-monthly');
});

/*
|--------------------------------------------------------------------------
| Companies
|--------------------------------------------------------------------------
*/
Route::prefix('companies')->name('companies.')->group(function () {
    // CompanyController: report and CRUD (the report comes first so `aggregate-report` is not taken as a company id)
    Route::controller(CompanyController::class)->group(function () {
        Route::get('aggregate-report', 'aggregateReport')->name('aggregate-report');
    });
    // CompanySupportTokenController: the admin enters a company's panel
    Route::post('{company}/login-as', [CompanySupportTokenController::class, 'store'])->name('loginAs');
});
Route::apiResource('companies', CompanyController::class);

/*
|--------------------------------------------------------------------------
| Insurers (insurance companies) and insurance agencies
|--------------------------------------------------------------------------
*/
// InsuranceCompanyController: the active insurers, for the agency forms and filters
Route::get('insurance-companies', [InsuranceCompanyController::class, 'index'])->name('insurance-companies.index');

Route::prefix('insurance-agencies/{insuranceAgency}')->name('insurance-agencies.')->group(function () {
    // InsuranceAgencySupportTokenController: the admin enters an agency's panel
    Route::post('login-as', [InsuranceAgencySupportTokenController::class, 'store'])->name('loginAs');

    // InsuranceAgencyController: connecting the agency to a company's insurance contract.
    // The admin looks at the connected companies, or picks one of the unconnected ones, then picks one of that company's
    // contracts (with the agency's insurer) to connect; a connected contract can be cut off and connected again.
    Route::controller(InsuranceAgencyController::class)->group(function () {
        // Companies: the connected ones, and the ones not connected yet (before the {company} route, so it is not taken as an id)
        Route::get('companies', 'companies')->name('companies.index');
        //        Route::get('companies/unconnected', 'unconnectedCompanies')->name('companies.unconnected');
        // The contracts of one company that can be connected to the agency
        Route::get('companies/{company}/insurances', 'companyInsurances')->name('companies.insurances.index');

        // Contracts: connected to the agency, connect, cut off
        Route::get('insurances', 'insurances')->name('insurances.index');
        //        Route::post('insurances', 'connectInsurance')->name('insurances.store');
        //        Route::delete('insurances/{insurance}', 'disconnectInsurance')->name('insurances.destroy');
    });
});
// InsuranceAgencyController: CRUD of the agents
Route::apiResource('insurance-agencies', InsuranceAgencyController::class)
    ->parameters(['insurance-agencies' => 'insuranceAgency']);

/*
|--------------------------------------------------------------------------
| Cities
|--------------------------------------------------------------------------
*/
Route::apiResource('cities', CityController::class);

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
Route::apiResource('notifications', NotificationController::class)
    ->only(['index', 'store', 'show']);

/*
|--------------------------------------------------------------------------
| Waybills
|--------------------------------------------------------------------------
*/
Route::prefix('waybills')->name('waybills.')->controller(WaybillController::class)->group(function () {
    Route::get('trashed', 'trashed')->name('trashed');
    Route::patch('{waybill}/restore', 'restore')->whereNumber('waybill')->name('restore');
});
Route::apiResource('waybills', WaybillController::class)->only(['index']);

/*
|--------------------------------------------------------------------------
| Report fields
|--------------------------------------------------------------------------
*/
Route::apiResource('report-fields', ReportFieldController::class)->parameters(['report-fields' => 'reportField']);

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/
Route::prefix('settings')->name('settings.')->controller(SettingController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::match(['put', 'patch'], '/', 'update')->name('update');
});

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/
Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
    Route::get('{user}/permissions', 'permissions')->name('permissions');
    Route::post('permissions/{user}', 'syncPermissions')->name('syncPermissions');
});
Route::apiResource('users', UserController::class);

/*
|--------------------------------------------------------------------------
| Permissions and roles
|--------------------------------------------------------------------------
*/
Route::apiResource('permissions', PermissionController::class)->except(['show']);
//    Route::post('permissions/sync/{role}', [PermissionController::class, 'syncWithRole'])->name('syncWithRole');
Route::apiResource('roles', RoleController::class);
