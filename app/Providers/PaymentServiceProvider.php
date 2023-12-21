<?php

namespace App\Providers;

use App\Models\Order;
use App\Services\Payment\LifepayPayment;
use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Tinkoff\TinkoffPayment;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider {
    public function register()
    {
        $this->app->singleton(PaymentInterface::class, function() {
//            if (request()->path() === 'payment/lifepay/webhook') {
//                return new LifepayPayment();
//            }
//            $uri = request()->getRequestUri();
//            $parts = explode('/', $uri);
//            if (!empty($parts[2]) && !empty($parts[4]) && $parts[2] === 'ats' && $parts[4] === 'done') {
//                $orderName = $parts[3];
//
//                $order = Order::where(['name' => $orderName])->first();
//                if ($order->payment->payment_system === 'lifepay') {
//                    return new LifepayPayment();
//                }
//            }

            return new TinkoffPayment();
        });
    }
}
