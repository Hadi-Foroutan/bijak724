<?php

use App\Http\Controllers\ReportExportDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary signed download link of a finished report export (see ReportExportResource::download_url).
Route::get('/report-exports/{reportExport}/download', ReportExportDownloadController::class)
    ->middleware('signed')
    ->name('report-exports.download');
