<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\ApiController;

class ResultController extends ApiController
{
    public function result_success ()
    {
        return view('payment.result');
    }
    public function result_error ()
    {
        return view('payment.result');
    }
}
