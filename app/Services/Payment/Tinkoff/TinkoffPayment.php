<?php

namespace App\Services\Payment\Tinkoff;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\System\SystemChangeStatusPaymentEvent;
use App\Events\System\SystemPaymentConfirmedEvent;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Exceptions\Custom\Payment\LifepayCantCreateLinkException;
use App\Exceptions\Custom\Payment\TinkoffCantCreateLinkExceptiion;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentDebug;
use App\Models\PaymentLog;
use App\Services\Payment\Lifepay\LifepayRequest;
use App\Services\Payment\PaymentInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TinkoffPayment implements PaymentInterface
{

    public function hold(Order $order): Payment
    {
        // Отменяем предыдущие платежи
        $order->payments()->update(['status' => PaymentStatus::CANCELED_USER]);

        // Создаём новый
        $payment = new Payment([
            'uuid'              => (string)Str::uuid(),
            'order_id'          => $order->id,
            'payment_system'    => 'tinkoff',
            'payment_method'    => 'creditcard',
            'sum'               => $order->cart->cast()->sumFinal,
            //'sum'               => '10',
            'extra->order_name' => $order->name,
            'extra->user_email' => $order->user->email,
        ]);

        // генерим линку для оплаты
        $payment->fill(['extra->paylink' => static::getPayLink($payment)]);
        $payment->save();

        // само приходит из вебхука
        //event(new UserChangeStatusPaymentEvent($payment, extra: ['status' => PaymentStatus::START]));
        return $payment;
    }

    public function unblock(Payment $payment): Payment
    {
        TinkoffRequest::unblock([
            'PaymentId' => $payment->extra->tid
        ])->json();
        return $payment;
    }

    public function charge(Order $order): Order
    {
        TinkoffRequest::charge([
            'PaymentId'    => $order->payment->extra->tid,
            'Amount' => (int)round($order->cart->cast()->sumFinal * 100)
        ])->json();
        $order->refresh();
        return $order;
    }

    public function detail(Payment $payment): Payment
    {
        // TODO: Implement detail() method.
    }

    public function log(string $type, array $request): ?PaymentLog
    {
        switch ($type) {
            case 'webhook':
            {
                $order = 0;
                $payment = 0;
                $paymentUuid = 0;
                if ($request['OrderId'] ?? false) {
                    $tmp = Order::where('name', $request['OrderId'] ?? null)->first();
                    if ($tmp) {
                        $order = $tmp->id;
                        $payment = $tmp->payment->id;
                        $paymentUuid = $tmp->payment->uuid;
                    }
                }

                PaymentDebug::create([
                    'system'          => 'tinkoff',
                    'order_id'        => $order,
                    'payment_id'      => $payment,
                    'side'            => 'in',
                    'request_url'     => request()->getUri(),
                    'request_method'  => request()->getMethod(),
                    'request_headers' => request()->header(),
                    'request_body'    => request()->getContent(),
                    'time'            => 0
                ]);

                $check = $request['Token'];
                $safe = Arr::except($request, ['Token']);

                $log = new PaymentLog([
                    'type'       => 'webhook',
                    'data'       => $safe,
                    'verify'     => $check === TinkoffRequest::check($safe),
                    'payment_id' => 0,
                ]);

                $payment = Payment::where('uuid', $paymentUuid ?? null)->first();
                if ($payment) {
                    $log->payment_id = $payment->id;
                    $log->success = true;

                    if ($payment->status !== PaymentStatus::REFUND) {
                        switch ($safe['Status']) {
                            case 'CONFIRMED':
                            case 'REJECTED':
                            case 'REVERSED':
                            case 'PARTIAL_REVERSED':
                            case 'PARTIAL_REFUNDED':
                            case 'REFUNDED':
                            case 'CANCELED':
                            {
                                if (empty($payment->confirmed_at)) {
                                    $update = [
                                        'confirmed_at' => Carbon::now(),
                                        'extra->tid'   => $safe['PaymentId']
                                    ];
                                    if ($safe['Status'] === 'CONFIRMED') {
                                        $update['status'] = PaymentStatus::DONE;
                                    }
                                    $payment->update($update);
                                    event(new SystemPaymentConfirmedEvent(
                                        $payment,
                                        extra: ['request' => $safe]
                                    ));
                                }
                                break;
                            }
                            case 'AUTHORIZED':
                            {
                                if ($payment->status !== PaymentStatus::HOLD) {
                                    $payment->update([
                                        'status'       => PaymentStatus::HOLD,
                                        'extra->tid'   => $safe['PaymentId'],
//                                        'confirmed_at' => Carbon::now()
                                    ]);
                                    event(new SystemChangeStatusPaymentEvent(
                                        $payment,
                                        extra: ['status' => PaymentStatus::HOLD, 'request' => $safe]
                                    ));

                                    $payment->order->update(['status' => OrderStatus::PAYMENT_DONE]);
                                    event(new UserChangeStatusOrderEvent(
                                        $payment->order,
                                        extra: ['status' => OrderStatus::PAYMENT_DONE]
                                    ));
                                }
                                break;
                            }
                        }
                    }
                }

                $log->save();
                return $log;
            }
        }

        return null;
    }

    public function getCardList (string $userId): array
    {
        $cards = TinkoffRequest::getCardList([
            'CustomerKey' => $userId
        ])->json();

        return $cards;
    }

    public function removeCard(string $cardId): array
    {
        $result = TinkoffRequest::removeCard([
            'CardId' => $cardId
        ])->json();

        return $result;
    }

    public static function getPayLink (Payment $payment): string
    {
        $link = $payment->extra->paylink ?? null;

        if (!$link) {
            $request = [
                'Amount'        => (int)round($payment->sum * 100), // в копейках
                'OrderId'       => $payment->order->name,
//                'PayType'       => 'T' //Двухстадийный
//                'name'     => "Оплата заказа № " . $payment->extra->order_name,
            ];
            $response = TinkoffRequest::init($request, true, $payment->order->user->uuid);

            if (!$response->json('PaymentURL')) {
                throw new TinkoffCantCreateLinkExceptiion();
            }

            $link = $response->json('PaymentURL');
        }

        return $link;
    }
}
