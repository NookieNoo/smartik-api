<?php

namespace App\Http\Controllers\User;

use App\Enums\PaymentStatus;
use App\Exceptions\Custom\CantCancelFinishException;
use App\Exceptions\Custom\CantCreatePayment;
use App\Exceptions\Vendor\AccessDeniedException;
use App\Http\Controllers\ApiController;
use App\Http\Resources\User\PaymentResource;
use App\Models\Order;
use App\Services\ActiveApi\Attributes\Title;
use App\Services\Payment\PaymentInterface;

#[
    Title('Оплата', 'payment'),
]
class PaymentController extends ApiController
{
    #[
        Title('Создание', 'create'),
    ]
    public function create (Order $order, PaymentInterface $paymentService)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }

        switch ($order->payment->status ?? null) {
            case PaymentStatus::START:
            case PaymentStatus::HOLD:
            {
                throw new CantCreatePayment('cancel old payments first');
            }
            case PaymentStatus::DONE:
            {
                throw new CantCreatePayment('payment is done');
            }
            default:
            {
                $paymentService->hold($order);
            }
        }

        $order->refresh();

        return $this->send(new PaymentResource($order->payment));
    }

    #[
        Title('Отмена', 'cancel'),
    ]
    public function cancel (Order $order, PaymentInterface $paymentService)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }

        $payment = $order->payment;

        switch ($payment->status) {
            case PaymentStatus::START:
            {
                $payment->status = PaymentStatus::CANCELED_USER;
                break;
            }
            case PaymentStatus::HOLD:
            {
                $payment = $paymentService->unblock($payment);
                break;
            }
            default:
            {
                throw new CantCancelFinishException;
            }
        }

        return $this->send(new PaymentResource($payment));
    }

    #[
        Title('Информация', 'info'),
    ]
    public function info (Order $order)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }

        return $this->send(new PaymentResource($order->payment));
    }

    #[
        Title('Список', 'list'),
    ]
    public function list (Order $order)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }

        return $this->send(PaymentResource::collection($order->payments));
    }
}