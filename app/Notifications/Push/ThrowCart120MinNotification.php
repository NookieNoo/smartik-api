<?php

namespace App\Notifications\Push;

use App\Notifications\PushNotification;

class ThrowCart120MinNotification extends PushNotification
{
    public function __construct () {}

    public function data (): array
    {
        return [
            'action'       => 'navigation',
            'data'         => json_encode([
                'screen' => 'TabNavigation',
                'params' => [
                    'screen' => 'Cart',
                ]
            ]),
            'af_push_link' => 'https://smartik.onelink.me/KzYq/ijedm3lj'
        ];
    }

    public function title (): string
    {
        return 'Почти готово! 😊';
    }

    public function body (): string
    {
        return "Не забудьте завершить свой заказ и получить все, что вы выбрали.";
    }
}