<?php

use App\Http\Controllers\Admin\City\CityController;
use App\Http\Controllers\Admin\Company\CompanyController;
use App\Http\Controllers\Admin\Company\CompanySupportTokenController;
use App\Http\Controllers\Admin\Permission\PermissionController;
use App\Http\Controllers\Admin\Permission\RoleController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('companies', CompanyController::class);
Route::prefix('companies')->name('companies.')->group(function () {
    Route::post('{company}/login-as', [CompanySupportTokenController::class, 'store'])->name('loginAs');
});

Route::apiResource('cities', CityController::class);

Route::apiResource('users', UserController::class);
Route::prefix('users')->name('users.')->group(function () {
    Route::post('permissions/{user}', [UserController::class, 'syncPermissions'])->name('syncPermissions');
});


Route::apiResource('permissions', PermissionController::class);
Route::apiResource('roles', RoleController::class);

Route::prefix('permissions')->name('permissions.')->group(function () {
//    Route::post('sync/{role}', [PermissionController::class, 'syncWithRole'])->name('syncWithRole');
});
