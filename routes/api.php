<?php

use App\Http\Controllers\ReferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('reference')->controller(ReferenceController::class)->group(function () {
    Route::get('minimum', 'minimum');
});

Route::prefix('user')->group(function () {
    require base_path('routes/api/user.php');
});
