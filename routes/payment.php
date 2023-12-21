<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Support\Facades\Route;

/*Route::middleware('auth:provider')->group(function () {
    Route::prefix('import')->controller(ImportController::class)->group(function () {
        Route::post('', 'import');
    });
    Route::prefix('export')->controller(ExportController::class)->group(function () {
        Route::get('', 'export');
        Route::post('', 'order');
    });
});*/

Route::prefix('lifepay')->controller(LifepayController::class)->group(function () {
    Route::post('webhook', 'webhook');
    Route::get('webhook', 'webhook');
});

Route::prefix('tinkoff')->controller(TinkoffController::class)->group(function () {
    Route::post('webhook', 'webhook');
    Route::get('webhook', 'webhook');
});

Route::any('success', [ResultController::class, 'result_success']);
Route::any('error', [ResultController::class, 'result_error']);
