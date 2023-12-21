<?php

namespace App\Notifications\Push;

use App\Models\Order;
use App\Notifications\PushNotification;

class AdminKkmErrorNotification extends PushNotification
{
    public function __construct (public Order $order, public string $json) {}

    public function data (): array
    {
        return [];
    }

    public function title (): string
    {
        return 'Ошибка кассы по заказу ' . $this->order->name . ' (' . $this->order->id . ')';
    }

    public function body (): string
    {
        return $this->json;
    }

}