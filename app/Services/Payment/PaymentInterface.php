<?php

namespace App\Services\Payment;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;

interface PaymentInterface
{
    public function hold (Order $order): Payment;

    public function unblock (Payment $payment): Payment;

    public function charge (Order $order): Order;

    public function detail (Payment $payment): Payment;

    public function getCardList (string $userId): array;

    public function removeCard (string $cardId): array;

    public function log (string $type, array $request): ?PaymentLog;
}
