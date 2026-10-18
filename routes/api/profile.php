<?php

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/profile', [ProfileController::class, 'update'])->name('update');
