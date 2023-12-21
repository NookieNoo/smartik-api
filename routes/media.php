<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

Route::controller(MediaController::class)->group(function () {
    Route::get('avatar/{uuid}/{type?}', 'avatar');
    Route::get('catalog/{uuid}/{type?}', 'catalog');
    Route::get('brand/{slug}/{type?}', 'brand');
    Route::get('product/{uuid}/{type?}', 'product');
    Route::get('banner/{uuid}/{type?}', 'banner');
});
