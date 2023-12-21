<?php

namespace App\Jobs\SDG;

use App\Enums\CartProductStatus;
use App\Enums\OrderSystemStatus;
use App\Enums\PaymentStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Events\System\SystemChangeSystemStatusOrderEvent;
use App\Exceptions\Integration\SDG\ParseSHPPaymentNotInHoldException;
use App\Models\CartProduct;
use App\Models\Integration;
use App\Models\Order;
use App\Models\ProductMark;
use App\Models\ProductPrice;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use App\Services\Payment\PaymentCancelCause;
use App\Services\Payment\PaymentInterface;
use App\Services\SDGTransport;
use App\Services\Showcase\CartService;
use Carbon\Carbon;

class SDGProcessSHPJob
{
    public function __construct (
        public string $file,
        public string $remote
    ) {}

    public function handle (PaymentInterface $paymentService, SDGTransport $transport)
    {
        try {
            $xml = simplexml_load_string(file_get_contents($this->file), "SimpleXMLElement", LIBXML_NOCDATA);
            $isFrozenShp = str_contains($xml->HEAD->attributes()->DLVNR, 'FREEZE');

            $order = Order::query()
                ->where(function ($query) use ($xml) {
                    $orderName = str_replace('_FREEZE', '', $xml->HEAD->attributes()->DLVNR);
                    $query->where('id', $xml->HEAD->attributes()->ORDNR)->orWhere('name', $orderName);
                })
                ->whereIn('system_status', [
                    OrderSystemStatus::SEND_TO_SDG_OUTBOUND,
                    OrderSystemStatus::GET_FROM_SDG_WBL,
                    OrderSystemStatus::GET_FROM_SDG_SHP, // ?
                ])
                ->first();

            if ($xml->HEAD->children()->LINE->count() === 0) {
                // todo: если нет строк в файле, значит заказ полностью не собран. надо бы отменять его тогда
                CartProduct::where('cart_id', $order->cart_id)->each(function ($item) use ($paymentService, $order) {
                    $item->update([
                        'status' => CartProductStatus::CANCELED_SDG
                    ]);
                    event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CANCELED_SDG]));
                });
                $paymentService->unblock($order->payment, PaymentCancelCause::MANAGER);
            } else {
                $shp = collect();
                foreach ($xml->HEAD->LINE as $line) {
                    $shp->add([
                        'product_id' => (int)$line->attributes()->MATNR,
                        'count'      => (int)$line->attributes()->MMENG,
                        'mark'       => (string)$line->attributes()->CRPTMARKS,
                        'expired_at' => Carbon::createFromFormat('Ymd', $line->attributes()->BBDDT)->format('Y-m-d')
                    ]);

                    if (!empty($line->attributes()->CRPTMARKS)) {
                        // todo: надо помнить, что в корзину может попасть один и тот же product_id, но с разными сроками
                        ProductMark::create([
                            'name'       => (string)$line->attributes()->CRPTMARKS,
                            'product_id' => (int)$line->attributes()->MATNR,
                            'order_id'   => $order->id
                        ]);
                    }
                }

                /* @var $product CartProduct */
                foreach ($order->cart->products as $product) {
                    if (($isFrozenShp && !$product->product->is_frozen) || (!$isFrozenShp && $product->product->is_frozen)) {
                        continue;
                    }

                    $product_price = ProductPrice::find($product->product_price_id);
                    $find_shp = $shp->where('product_id', $product->product_id)->where('expired_at', $product_price->expired_at->format('Y-m-d'))->sum('count');
                    $cart_product = CartProduct::where('cart_id', $order->cart_id)->where('product_price_id', $product_price->id)->first();

                    if ($cart_product && $find_shp) {
                        // складываем количество, нужно для маркируемых товаров.
                        $count = $shp->where('product_id', $product->product_id)->where('expired_at', $product_price->expired_at->format('Y-m-d'))->sum('count');
                        if ($count !== (int)$product->count) {
                            $cart_product->update(['status' => CartProductStatus::CANCELED_SDG]);
                            event(new SystemChangeStatusCartProductEvent($cart_product, extra: ['status' => CartProductStatus::CANCELED_SDG]));
                            if ($count) {
                                $new_product = $cart_product->replicate()->fill([
                                    'count'  => $find_shp,
                                    'status' => CartProductStatus::WAREHOUSE,
                                ]);
                                $new_product->save();
                                event(new SystemChangeStatusCartProductEvent($new_product, extra: ['status' => CartProductStatus::WAREHOUSE]));
                            }
                        }
                    } else {
                        $cart_product->update(['status' => CartProductStatus::CANCELED_SDG]);
                        event(new SystemChangeStatusCartProductEvent($cart_product, extra: ['status' => CartProductStatus::CANCELED_SDG]));
                    }
                }
                // было тестово для СДГ
                //$paymentService->charge($order);
            }

            $cast = $order->cart->cast();
            $order->update([
                'system_status' => OrderSystemStatus::GET_FROM_SDG_SHP,
                'sum_products'  => $cast->sumProducts,
                'sum_final'     => $cast->sumFinal,
            ]);
            event(new SystemChangeSystemStatusOrderEvent($order, extra: ['system_status' => OrderSystemStatus::GET_FROM_SDG_SHP]));

            Integration::create([
                'date' => Carbon::now(),
                'type' => ImapXlsType::SHP,
                'data' => (array)$xml,
            ]);

            $transport->move($this->remote, 'Out/Ok');
        } catch (\Exception $e) {
            $transport->move($this->remote, 'Out/Bad', 'payment_not_in_hold');
            throw $e;
        }
    }
}
