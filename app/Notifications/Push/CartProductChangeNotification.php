<?php

namespace App\Notifications\Push;

use App\Models\CartProduct;
use App\Notifications\PushNotification;

class CartProductChangeNotification extends PushNotification
{
    public function __construct (public CartProduct $product) {}

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
                            'uuid' => $this->product->cart->order->uuid,
                        ]
                    ]
                ]
            ])
        ];
    }

    public function title (): string
    {
        return 'Изменение в заказе ' . $this->product->cart->order->name;
    }

    public function body (): string
    {
        return "Изменилась стоимость заказа. Некоторые продукты, к сожалению, закончились. Подробности внутри.";
    }

}
