<?php

namespace App\Http\Controllers\External;

use Illuminate\Support\Facades\Route;


Route::prefix('ats')->controller(AtsController::class)->group(function () {

    Route::post('{order}/performed', 'performed');
    Route::post('{order}/on_way', 'on_way');
    Route::post('{order}/in_radius', 'in_radius');
    Route::post('{order}/done', 'done')->name('ats.done');
    Route::post('{order}/cancel', 'cancel');
    Route::post('{order}/test', 'test');
});
