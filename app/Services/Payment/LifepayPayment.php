<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\System\SystemChangeStatusPaymentEvent;
use App\Events\System\SystemPaymentConfirmedEvent;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Exceptions\Custom\Payment\LifepayCantCreateLinkException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentDebug;
use App\Models\PaymentLog;
use App\Services\Payment\Lifepay\LifepayRequest;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LifepayPayment implements PaymentInterface
{
    public function hold (Order $order): Payment
    {
        // Отменяем предыдущие платежи
        $order->payments()->update(['status' => PaymentStatus::CANCELED_USER]);

        // Создаём новый
        $payment = new Payment([
            'uuid'              => (string)Str::uuid(),
            'order_id'          => $order->id,
            'payment_system'    => 'lifepay',
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

    public function unblock (Payment $payment, PaymentCancelCause $cause = PaymentCancelCause::USER): Payment
    {
        LifepayRequest::unblock([
            'tid' => $payment->extra->tid
        ])->json();
        return $payment;
    }

    public function charge (Order $order): Order
    {
        LifepayRequest::charge([
            'tid'    => $order->payment->extra->tid,
            'amount' => $order->cart->cast()->sumFinal
        ])->json();
        $order->refresh();
        return $order;
    }

    public function detail (Payment $payment): Payment
    {
        $request = LifepayRequest::details([
            'order_id' => $payment->uuid
        ])->json();

        $this->log('ping', $request);

        $payment->refresh();
        return $payment;
    }

    public function log (string $type, array $request): ?PaymentLog
    {
        switch ($type) {
            case 'webhook':
            {

                $order = 0;
                $payment = 0;
                if ($request['order_id'] ?? false) {
                    $tmp = Payment::where('uuid', $request['order_id'] ?? null)->first();
                    if ($tmp) {
                        $order = $tmp->order->id;
                        $payment = $tmp->id;
                    }
                }

                PaymentDebug::create([
                    'system'          => 'lifepay',
                    'order_id'        => $order,
                    'payment_id'      => $payment,
                    'side'            => 'in',
                    'request_url'     => request()->getUri(),
                    'request_method'  => request()->getMethod(),
                    'request_headers' => request()->header(),
                    'request_body'    => request()->getContent(),
                    'time'            => 0
                ]);

                $check = $request['check'];
                $safe = Arr::except($request, ['check', 'mac']);

                $log = new PaymentLog([
                    'type'       => 'webhook',
                    'data'       => $safe,
                    'verify'     => $check === LifepayRequest::check($safe),
                    'payment_id' => 0,
                ]);

                $payment = Payment::where('uuid', $safe['order_id'] ?? null)->first();
                if ($payment) {
                    $log->payment_id = $payment->id;
                    $log->success = true;

                    if ($payment->status !== PaymentStatus::REFUND) {
                        switch ($safe['command']) {
                            case 'done':
                            case 'success':
                            case 'cancel':
                            case 'refund':
                            {
                                $update = [
                                    'confirmed_at' => Carbon::now(),
                                    'extra->tid'   => $safe['tid']
                                ];
                                if ($safe['command'] === 'success') {
                                    $update['status'] = PaymentStatus::DONE;
                                }
                                $payment->update($update);
                                event(new SystemPaymentConfirmedEvent(
                                    $payment,
                                    extra: ['request' => $safe]
                                ));

                                break;
                            }
                            case 'funds_blocked':
                            {
                                $payment->update([
                                    'status'       => PaymentStatus::HOLD,
                                    'extra->tid'   => $safe['tid'],
                                    'confirmed_at' => Carbon::now()
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
                                break;
                            }
                        }
                    }
                }

                $log->save();
                return $log;
            }
            case 'ping':
            {
                $success = $request['status'] === 'success' ?? false;
                $log = new PaymentLog([
                    'type'   => 'ping',
                    'data'   => $request,
                    'verify' => true,
                ]);

                if ($success) {
                    $payment = Payment::where('uuid', $request['order_id'] ?? null)->first();
                    if ($payment) {
                        $log->payment_id = $payment->id;

                        $status = match ($request['transaction_status']) {
                            'error'   => 'error',
                            'payed',
                            'success' => 'done',
                            default   => false
                        };
                        $log->success = true;

                        $update = [
                            'extra->tid' => $request['tid']
                        ];
                        if ($status) {
                            $update['status'] = $status;
                        }
                        //$payment->update($update);
                    }
                }

                $log->save();
                return $log;
            }
        }

        return null;
    }

    public static function getPayLink (Payment $payment): string
    {
        $link = $payment->extra->paylink ?? null;

        if (!$link) {
            $request = [
                'cost'     => $payment->sum,
                'order_id' => $payment->uuid,
                'name'     => "Оплата заказа № " . $payment->extra->order_name,
            ];
            $response = LifepayRequest::input($request);

            if (!$response->header('location')) {
                throw new LifepayCantCreateLinkException;
            }

            $link = $response->header('location');
        }

        $tmp = parse_url($link);
        if (!isset($tmp['host'])) $link = 'https://partner.life-pay.ru' . $link;

        return $link;
    }

    public function getCardList(string $userId): array
    {
        // TODO: Implement getCardList() method.
    }

    public function removeCard(string $cardId): array
    {
        // TODO: Implement removeCard() method.
    }
}
