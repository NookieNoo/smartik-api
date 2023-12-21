<?php

namespace App\Http\Controllers\Kkm;

use Illuminate\Support\Facades\Route;

Route::post('webhook', [LifepayController::class, 'webhook']);