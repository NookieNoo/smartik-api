<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\ApiController;
use App\Services\Payment\PaymentInterface;
use Illuminate\Http\Request;

class LifepayController extends ApiController
{
    public function webhook (Request $request, PaymentInterface $paymentService)
    {
        $paymentService->log('webhook', $request->all());
        return $this->send(true);
    }
}