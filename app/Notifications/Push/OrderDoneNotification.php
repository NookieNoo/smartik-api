<?php

namespace App\Notifications\Push;

use App\Models\Order;
use App\Notifications\PushNotification;

class OrderDoneNotification extends PushNotification
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
        return 'Заказ выполнен ' . $this->order->name;
    }

    public function body (): string
    {
        return "Узнать итоговую цену, посмотреть чек и получить поддержку вы можете в приложении.";
    }
}