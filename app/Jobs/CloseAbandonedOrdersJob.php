<?php

namespace App\Jobs;

use App\Enums\CartProductStatus;
use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Events\System\SystemChangeStatusCartEvent;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Events\System\SystemChangeStatusOrderEvent;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CloseAbandonedOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct (protected int $hoursToClose = 12) {}

    public function handle ()
    {
        Order::whereIn('status', [
            OrderStatus::CREATED,
            OrderStatus::PAYMENT_PROCESS
        ])->where('updated_at', '<=', Carbon::now()->subHours($this->hoursToClose))->each(function ($order) {
            $order->update([
                'status' => OrderStatus::CANCELED_MANAGER
            ]);
            event(new SystemChangeStatusOrderEvent($order, extra: [
                'status' => OrderStatus::CANCELED_MANAGER, 'cause' => 'abandoned over ' . $this->hoursToClose . ' hours'
            ]));

            $cart = $order->cart;
            $cart->update(['status' => CartStatus::CANCELED_TIME]);
            event(new SystemChangeStatusCartEvent($cart, extra: ['status' => CartStatus::CANCELED_TIME]));
            $cart->products->each(function ($item) {
                $item->update(['status' => CartProductStatus::CANCELED_ACTUAL]);
                event(new SystemChangeStatusCartProductEvent($item, extra: [
                    'status' => CartProductStatus::CANCELED_ACTUAL,
                    'cause'  => 'abandoned over ' . $this->hoursToClose . ' hours'
                ]));
            });
        });
    }
}