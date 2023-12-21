<?php

namespace App\Services;

use App\Data\CartCastData;
use App\Enums\CartProductStatus;
use App\Events\System\SystemKkmCancelEvent;
use App\Events\System\SystemKkmFinalEvent;
use App\Events\System\SystemKkmHoldEvent;
use App\Models\KkmCheck;
use App\Models\KkmLog;
use App\Models\Order;
use App\Models\ProductMark;
use App\Models\User;
use App\Notifications\Push\AdminKkmErrorNotification;
use App\Services\KKM\Enums\KKMMode;
use App\Services\KKM\Enums\KKMPaymentType;
use App\Services\KKM\Enums\KKMProductType;
use App\Services\KKM\Enums\KKMTaxSystem;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KKMService
{
    public string   $uri    = 'https://sapi.life-pay.ru/cloud-print-ffd1_2/create-receipt';
    protected bool  $test   = true;
    protected array $checks = [];

    public function __construct (
        protected Order        $order,
        protected CartCastData $cast,
        protected string       $type,
        public string          $login = '79031650035',
        public string          $apikey = '3b170ad95486bf5cd8452cc1996a44a1',
    )
    {
        if (App::isProduction()) {
            $this->test = false;
        }
    }

    public static function hold (Order $order)
    {
        $kkm = new static($order, $order->cart->cast(), 'hold');

        event(new SystemKkmHoldEvent($order, extra: [
            'cast' => $kkm->cast,
            'all'  => $kkm->products($kkm->order->cart->products, KKMProductType::FULL_PREPAYMENT),
            'good' => $kkm->products($kkm->order->cart->products()->whereNotIn('status', CartProductStatus::canceled())->get(), KKMProductType::FULL_PREPAYMENT),
            'bad'  => $kkm->products($kkm->order->cart->products()->whereIn('status', CartProductStatus::canceled())->get(), KKMProductType::FULL_PREPAYMENT),
        ]));

        $kkm->prepayment();
        return $kkm;
    }

    public static function final (Order $order, bool $prepayment = true)
    {
        $kkm = new static($order, $order->cart->cast(), 'final');

        event(new SystemKkmFinalEvent($order, extra: [
            'prepayment' => $prepayment,
            'cast'       => $kkm->cast,
            'all'        => $kkm->products($kkm->order->cart->products, KKMProductType::FULL_PAYMENT),
            'good'       => $kkm->products($kkm->order->cart->products()->whereNotIn('status', CartProductStatus::canceled())->get(), KKMProductType::FULL_PAYMENT),
            'bad'        => $kkm->products($kkm->order->cart->products()->whereIn('status', CartProductStatus::canceled())->get(), KKMProductType::FULL_PAYMENT),
        ]));

        $kkm->payment($prepayment);
        return $kkm;
    }

    public static function cancel (Order $order, ?KkmCheck $check = null)
    {
        $kkm = new static($order, $order->cart->cast(), 'cancel');
        if ($check) {

            event(new SystemKkmCancelEvent($order, extra: [
                'check' => $check,
            ]));

            $kkm->refund_check($check);
        } else {

            event(new SystemKkmCancelEvent($order, extra: [
                'cast'     => $kkm->cast,
                'products' => $order->cart->products,
            ]));

            $kkm->refund();
        }
        return $kkm;
    }

    protected function products (Collection|Enumerable $products, KKMProductType $type, bool $bad = false): array
    {
        $result = [];

        if (!$products->count()) return $result;
        $products->each(function ($item, $index) use (&$result, $type) {
            $data = [
                'key'       => $index,
                'name'      => $item->name,
                'price'     => $item->price,
                'quantity'  => $item->count,
                'tax'       => 'vat' . $item->vat,
                'type'      => $type->value,
                'item_type' => 1
            ];


            if ($type === KKMProductType::FULL_PAYMENT) {
                $marks = ProductMark::where([
                    'order_id'   => $this->order->id,
                    'product_id' => $item->product_id,
                ])->get();

                foreach ($marks as $mark) {
                    $result[] = [
                        ...$data,
                        'quantity'            => 1.0,
                        'marking_code'        => static::markToKkm($mark->name),
                        'item_type'           => 33,
                        'marking_code_status' => 1
                    ];
                }

                // todo: грязный костыль, если марок меньше, чем позиций в заказе, нам всё равно надо положить
                // товары в чек, чтобы билась сумма итп, иначе касса не примет такой чек.
                if ($marks->count() < $data['quantity']) {
                    $result[] = [
                        ...$data,
                        'quantity' => $data['quantity'] - $marks->count(),
                    ];
                }
            } else {
                $result[] = $data;
            }

        });

        if ($bad) {
            if ($this->cast->deliveryCancelByCanceled && !$this->cast->deliveryCancelByPromo && !$this->cast->deliveryCancelByLogic) {
                $result[] = [
                    'name'     => "Доставка",
                    'price'    => $this->cast->deliveryPrice,
                    'quantity' => 1,
                    'tax'      => 'vat20',
                    'type'     => $type->value,
                ];
            }
        } else {
            if ($this->cast->deliveryPriceFinal) {
                $result[] = [
                    'name'     => "Доставка",
                    'price'    => $this->cast->deliveryPriceFinal,
                    'quantity' => 1,
                    'tax'      => 'vat20',
                    'type'     => $type->value,
                ];
            }
        }

        return $result;
    }

    protected function header (bool $prepayment = false, array $additional = []): array
    {
        $sum = $this->cast->sumFinal;

        $request = [
            'mode'         => KKMMode::EMAIL->value,
            //'customer_email' => 'kkm.check+' . $this->order->user->system_name . '@smartik.me',
            'type'         => KKMPaymentType::PAYMENT->value,
            'ext_id'       => ($additional['type'] ?? KKMPaymentType::PAYMENT->value) . '-' . ($additional['product_type'] ?? '0') . '-' . $this->order->id,
            'order_number' => $this->order->name,
            'callback_url' => config('app.url') . '/kkm/webhook',
            'tax_system'   => KKMTaxSystem::OSN->value
        ];

        if ($this->order->user->email) {
            $request['customer_email'] = $this->order->user->email ?? 'kkm.check+' . $this->order->user->system_name . '@smartik.me';
        } else {
            $request['customer_phone'] = $this->order->user->phone->value;
        }

        if ($prepayment) {
            $request['prepayment_amount'] = $sum;
        } else {
            $request['card_amount'] = $sum;
        }

        if (isset($additional['product_type'])) unset($additional['product_type']);

        if (count($additional)) {
            $request = [
                ...$request,
                ...$additional
            ];
        }

        return $request;
    }

    protected function payment (bool $prepayment = true): self
    {
        if ($this->cast->products->toCollection()->filter(fn ($item) => !$item->canceled)->count()) {
            $check = $this->header($prepayment, [
                'product_type' => KKMProductType::FULL_PAYMENT->value
            ]);
            $check['purchase']['products'] = $this->products($this->cast->products->toCollection()->filter(fn ($item) => !$item->canceled), KKMProductType::FULL_PAYMENT);
            $this->checks[] = $check;
        }

        if ($this->cast->products->toCollection()->filter(fn ($item) => $item->canceled)->count() && $prepayment) {
            $this->refund();
        }

        return $this;
    }


    protected function prepayment (): self
    {
        $request = $this->header(additional: [
            'product_type' => KKMProductType::FULL_PREPAYMENT->value
        ]);
        $request['purchase']['products'] = $this->products($this->cast->products->toCollection()->filter(fn ($item) => !$item->canceled), KKMProductType::FULL_PREPAYMENT);

        $this->checks[] = $request;

        return $this;
    }

    protected function refund (): self
    {
        $request = $this->header(additional: [
            'type'         => KKMPaymentType::REFUND->value,
            'product_type' => KKMProductType::FULL_PREPAYMENT->value,
            'card_amount'  => $this->cast->sumCanceled
        ]);
        $request['purchase']['products'] = $this->products($this->cast->products->toCollection()->filter(fn ($item) => $item->canceled), KKMProductType::FULL_PREPAYMENT, true);

        $this->checks[] = $request;

        return $this;
    }

    protected function refund_check (KkmCheck $check): self
    {
        $request = $check->check;
        $request['type'] = KKMPaymentType::REFUND->value;
        $request['ext_id'] = KKMPaymentType::REFUND->value . '-' . KKMProductType::FULL_PREPAYMENT->value . '-' . $this->order->id;
        $this->checks[] = $request;
        return $this;
    }

    public function send (bool $test = false): array|false
    {
        $this->test = $test;
        if (!app()->environment('production')) {
            $this->test = true;
        }
        return $this->requestToKKM();
    }

    protected function requestToKKM (): array|false
    {
        $result = [];
        foreach ($this->checks as $item) {
            $request = [
                'apikey' => $this->apikey,
                'login'  => $this->login,
                'test'   => (int)$this->test,
                ...$item
            ];


            $check = new KkmCheck([
                'order_id' => $this->order->id,
                'type'     => match (KKMPaymentType::tryFrom($item['type'])) {
                    KKMPaymentType::REFUND  => 'cancel',
                    KKMPaymentType::PAYMENT => $this->type,
                    default                 => 'cancel'
                },
                'check'    => $item
            ]);

            $response = Http::after(function ($request, $response, $time) {
                KkmLog::create([
                    'request'       => (string)$request->getBody(),
                    'request_raw'   => Message::toString($request),
                    'response_code' => $response->getStatusCode(),
                    'response'      => (string)$response->getBody(),
                    'response_raw'  => Message::toString($response),
                    'response_time' => $time
                ]);
            })->post($this->uri, $request);

            if ($response->ok()) {
                $check->uuid = data_get($response->json(), 'data.uuid') ?? substr_replace(Str::uuid(), 'test', -4);
                $check->save();
                $result[] = $response->json();
            } else {
                User::find(15)->notify(new AdminKkmErrorNotification($this->order, $response->json()));
            }
        }
        if (count($result)) {
            return $result;
        }
        return false;
    }

    /*
     * Трансляция марки со сканера в марку для кассы. Фактически, добавление спец-разделителя, без которого
     * ЧЗ очень не хочет работать
     */
    protected static function markToKkm (string $mark, string $delimiter = "\x1D"): string
    {
        return substr($mark, 0, -6) . $delimiter . substr($mark, -6);
    }
}
