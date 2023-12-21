<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\ApiController;
use App\Services\Payment\PaymentInterface;
use Illuminate\Http\Request;

class TinkoffController extends ApiController
{
    public function webhook (Request $request, PaymentInterface $paymentService)
    {
        $paymentService->log('webhook', $request->all());
        return $this->sendRaw('OK');
    }
}
