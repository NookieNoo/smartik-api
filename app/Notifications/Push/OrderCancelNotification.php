<?php

namespace App\Notifications\Push;

use App\Models\Order;
use App\Notifications\PushNotification;

class OrderCancelNotification extends PushNotification
{
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
        return 'Заказ отменён ' . $this->order->name;
    }

    public function body (): string
    {
        return "Подробности в приложении.";
    }

}