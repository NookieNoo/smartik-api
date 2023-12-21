<?php

namespace App\Notifications\Push;

use App\Models\Order;
use App\Notifications\PushNotification;

class OrderDeliveryOnWayNotification extends PushNotification
{
    public ?string $throttleKey = 'delivery';

    public function __construct (public Order $order) {}

    public function data (): array
    {
        return [
            'action' => 'navigation',
            'data'   => json_encode([
                'screen' => 'ModalNavigation',
                'params' => [
                    'screen' => 'OrderScreen',
                    'params' => [
                        'order' => [
                            'uuid' => $this->order->uuid
                        ]
                    ]
                ]
            ])
        ];
    }

    public function title (): string
    {
        return 'Доставка заказа ' . $this->order->name;
    }

    public function body (): string
    {
        return "Водитель выехал на ваш адрес, совсем скоро он привезёт вам заказ.";
    }
}
