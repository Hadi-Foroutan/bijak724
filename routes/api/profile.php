<?php

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/profile', [ProfileController::class, 'show'])->name('show');
Route::post('/profile', [ProfileController::class, 'update'])->name('update');
