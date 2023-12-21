<?php

namespace App\Listeners;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Notifications\Push\CartProductChangeNotification;

class CartProductSubscriber
{

    public function status ($event)
    {
        $product = $event->data;
        $status = $event->extra['status'];

        switch ($status ?? null) {
            case CartProductStatus::CANCELED_ACTUAL:
            case CartProductStatus::CANCELED_PROVIDER:
            case CartProductStatus::CANCELED_SDG:
            case CartProductStatus::CANCELED_USER:
            {
                if ($product->cart?->order?->status === OrderStatus::PAYMENT_DONE) {
                    $product->cart?->user?->notify(new CartProductChangeNotification($product));
                }
                break;
            }
        }
    }

    public function subscribe ($events): array
    {
        return [
            SystemChangeStatusCartProductEvent::class => 'status',
        ];
    }
}