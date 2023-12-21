<?php

namespace App\Notifications\Push;

use App\Notifications\PushNotification;

class ThrowCart30MinNotification extends PushNotification
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
            'af_push_link' => 'https://smartik.onelink.me/KzYq/fl2p5sd5'
        ];
    }

    public function title (): string
    {
        return 'Товары в корзине ждут вас!';
    }

    public function body (): string
    {
        return "Остался всего один шаг, чтобы завершить заказ. Успейте сделать его!";
    }
}