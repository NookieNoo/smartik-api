<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

Route::controller(FileController::class)->group(function () {
    Route::get('mail/{id}', 'mail');
    Route::get('report/{id}', 'report');
});