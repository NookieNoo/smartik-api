<?php

namespace App\Listeners;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderSystemStatus;
use App\Enums\PaymentStatus;
use App\Events\Admin\AdminChangeStatusOrderEvent;
use App\Events\System\SystemChangeStatusOrderEvent;
use App\Events\System\SystemChangeSystemStatusOrderEvent;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Jobs\SDG\SDGSendOutboundJob;
use App\Models\KkmCheck;
use App\Models\Promo;
use App\Models\User;
use App\Models\UserPromo;
use App\Notifications\Push\OrderCancelNotification;
use App\Notifications\Push\OrderDeliveryOnWayNotification;
use App\Notifications\Push\OrderDeliveryPerformedNotification;
use App\Notifications\Push\OrderDoneNotification;
use App\Services\KKMService;
use App\Services\Payment\LifepayPayment;
use App\Services\Payment\PaymentInterface;
use App\Services\Payment\Tinkoff\TinkoffPayment;
use App\Services\ShowcaseService;
use Illuminate\Support\Str;

class OrderSubscrber
{
    public PaymentInterface $paymentService;
    public function __construct (PaymentInterface $paymentService) {
        $this->paymentService = $paymentService;
    }
    public function status ($event)
    {
        $showcase = new ShowcaseService();
        $order = $event->data;

        switch ($event->extra['status'] ?? null) {
            case OrderStatus::DELIVERY_PERFORMED:
            {
                $event->data->user?->notify(new OrderDeliveryPerformedNotification($order));
                break;
            }
            case OrderStatus::DELIVERY_ON_WAY:
            {
                $event->data->user?->notify(new OrderDeliveryOnWayNotification($order));
                break;
            }
            case OrderStatus::DONE:
            {
                // списания бабок
            //    $paymentService = new TinkoffPayment(); //@FIXME inject
//                $paymentService = new LifepayPayment();
                $this->paymentService->charge($order);

                $order->cart->update(['final_cast' => $order->cart->cast()]);

                // финализируем чеки
                $prepayment = true;
                $hold = KkmCheck::where('order_id', $order->id)->where('type', 'hold')->first();
                $cancel = KkmCheck::where('order_id', $order->id)->where('type', 'cancel')->first();
                if (!$hold || $cancel) {
                    $prepayment = false;
                }
                KKMService::final($order, $prepayment)->send();

                // шлём пуш
                $order->user->notify(new OrderDoneNotification($order));

                // если был персональный промокод -- активируем его
                if ($order->extra['user_promo'] ?? false) {
                    Promo::find($order->extra['user_promo'])->update(['active' => true]);
                }
                break;
            }
            case OrderStatus::PAYMENT_DONE:
            {
                $showcase->order($order)->holdProducts();

                $order->cart->update(['prepayment_cast' => $order->cart->cast()]);

                KKMService::hold($order)->send();

                $order->cart->status = CartStatus::DONE;
                $order->cart->order_id = $order->id;
                $order->cart->save();

                dispatch(new SDGSendOutboundJob(extra: $order));

                // если был персональный промо, накидываем автору промокод
                if ($personal = User::checkPersonalPromo($order->cart->promos?->first()?->code)) {
                    $promo = Promo::create([
                        ...$personal['promo'],
                        'code'   => Str::upper(Str::random(6)),
                        'active' => false
                    ]);
                    UserPromo::create([
                        'user_id'      => $personal['user_id'],
                        'from_user_id' => $order->user_id,
                        'promo_id'     => $promo->id,
                    ]);
                    $promo->attachTag('Referal Program', 'promo');

                    $order->update(['extra->user_promo' => $promo->id]);
                }

                // если это персональный промо для автора, то пишем стату
                if ($order->cart->promos()->first()?->personal) {
                    $order->cart->promos()->first()->personal->update(['used_at' => now()]);
                }
                break;
            }
            case OrderStatus::CANCELED_DRIVER:
            case OrderStatus::CANCELED_MANAGER:
            case OrderStatus::CANCELED_USER:
            {
                if ($order->payment->status === PaymentStatus::HOLD) {
                    // $paymentService = new TinkoffPayment(); //FIXME
//                   $paymentService = new LifepayPayment();
                    $this->paymentService->unblock($order->payment);
                }

                // не нужно, всё случится через вебхук
                //$order->payment->status = PaymentStatus::REFUND;

                // чек нужен сразу, не дожидаемся подтверждения холда
                $hold = KkmCheck::where('order_id', $order->id)->where('type', 'hold')->first();
                $cancel = KkmCheck::where('order_id', $order->id)->where('type', 'cancel')->first();
                if ($hold && !$cancel) {
                    KKMService::cancel($order, $hold)->send();
                }

                // пуш
                $order->user?->notify(new OrderCancelNotification($order));

                // возвращаем на сток товар
                $showcase->order($order)->unHoldProducts();
                break;
            }
        }
    }

    public function system_status ($event)
    {
        $showcase = new ShowcaseService();
        $order = $event->data;

        switch ($event->extra['system_status'] ?? null) {
            case OrderSystemStatus::GET_FROM_PROVIDER:
            {
                $showcase->order($order)->holdProducts();
                break;
            }
        }
    }

    public function subscribe ($events): array
    {
        return [
            UserChangeStatusOrderEvent::class         => 'status',
            AdminChangeStatusOrderEvent::class        => 'status',
            SystemChangeStatusOrderEvent::class       => 'status',
            SystemChangeSystemStatusOrderEvent::class => 'system_status',
        ];
    }
}
