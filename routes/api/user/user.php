<?php

use App\Http\Controllers\User\Dashboard\DashboardController;
use App\Http\Controllers\User\Driver\DriverController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard',[DashboardController::class,'index'])->name('dashboard.index');

Route::apiResource('drivers', DriverController::class);
