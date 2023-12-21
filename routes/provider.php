<?php

namespace App\Http\Controllers\Provider;

use Illuminate\Support\Facades\Route;

Route::middleware('auth:provider')->group(function () {
    Route::prefix('import')->controller(ImportController::class)->group(function () {
        Route::post('', 'import');
    });
    Route::prefix('export')->controller(ExportController::class)->group(function () {
        Route::get('', 'export');
        Route::post('', 'order');
    });
});