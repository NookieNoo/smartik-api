<?php

namespace App\Services\Integration;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderSystemStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Events\System\SystemChangeSystemStatusOrderEvent;
use App\Jobs\SDG\SDGSendInboundJob;
use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Integration;
use App\Models\Order;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BaseIntegration
{
    protected static function baseBuilderToProvider ($builder, $provider)
    {
        return $builder->select()
            ->leftJoin('cart_products', 'cart_products.cart_id', 'orders.cart_id')
            ->leftJoin('product_prices', 'product_prices.id', 'cart_products.product_price_id')
            ->where('orders.status', OrderStatus::PAYMENT_DONE)
            ->where('cart_products.from_stock', false)
            ->where('product_prices.provider_id', $provider->id)
            ->where('orders.created_at', '>=', Carbon::parse('yesterday 09:00')->subDays(3));
    }

    public static function getProductsToProvider (Provider|int $provider, OrderSystemStatus $status = OrderSystemStatus::IN_APP): Collection
    {
        if (is_int($provider)) $provider = Provider::findOrFail($provider);

        $query = static::baseBuilderToProvider(DB::table('orders'), $provider)
            ->select([
                'cart_products.count',
                'orders.id as order_id',
                'products.id as product_id',
                'products.name',
                'product_prices.id as product_price_id',
                'product_prices.expired_at',
                'provider_products.external_id',
                DB::raw('(SELECT ean FROM product_eans WHERE product_id=products.id LIMIT 1) as product_ean'),
            ])
            ->leftJoin('products', 'products.id', 'cart_products.product_id')
            ->leftJoin('provider_products', 'provider_products.product_id', 'products.id');

        if ($status === OrderSystemStatus::IN_APP) {
            $query
                ->whereIn('orders.system_status', [OrderSystemStatus::IN_APP, OrderSystemStatus::SEND_TO_PROVIDER])
                ->where('cart_products.status', CartProductStatus::START);
        } else {
            $query->where('orders.system_status', $status);
        }

        $prices = $query->get();

        // todo remove работа менедежеров
        /*$prices->map(function ($price) use ($provider) {
            $external = ProviderProduct::where('provider_id', $provider->id)->where('external_id', $price->external_id)->first();
            if ($external && !empty($external->extra['multiple'])) {
                $price->count = ceil($price->count / $external->extra['multiple']) * $external->extra['multiple'];
            }
            return $price;
        });*/

        return $prices;
    }

    public static function getOrdersToProvider (Provider|int $provider, OrderSystemStatus|array $status = OrderSystemStatus::IN_APP): array
    {
        if (is_int($provider)) $provider = Provider::findOrFail($provider);
        if (!is_array($status)) {
            $status = [$status];
        }

        $prices = static::baseBuilderToProvider(DB::table('orders'), $provider)
            ->select(['orders.id'])
            ->whereIn('orders.system_status', $status)
            ->groupBy('orders.id')
            ->get();

        return $prices->pluck('id')->toArray();
    }

    public static function updateOrdersToProvider (int $provider)
    {
        $orders = static::getOrdersToProvider($provider);
        foreach ($orders as $item) {
            $order = Order::find($item);
            if ($order) {
                $order->update([
                    'system_status' => OrderSystemStatus::SEND_TO_PROVIDER
                ]);
                event(new SystemChangeSystemStatusOrderEvent($order, extra: ['system_status' => OrderSystemStatus::SEND_TO_PROVIDER]));
            }
        }
    }

    public static function updateOrdersFinalFromProvider (array $products, ?Integration $integration = null)
    {
        $carts = false;
        $orders = false;

        foreach ($products as $product) {
            if (!$carts) {
                $orders = static::getOrdersToProvider($product['provider_id'], OrderSystemStatus::SEND_TO_PROVIDER);
                $carts = Cart::query()
                    ->whereIn('order_id', $orders)
                    ->pluck('id')
                    ->toArray();
            }

            $exists = (int)$product['finish_count'];
            $need = (int)$product['need_count'];

            // если товара нет
            if ($exists === 0) {
                CartProduct::query()
                    ->whereIn('cart_id', $carts)
                    ->where('product_price_id', $product['product_price_id'])
                    ->each(function ($item) {
                        $item->update([
                            'status' => CartProductStatus::CANCELED_PROVIDER
                        ]);
                        event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CANCELED_PROVIDER]));
                    });
            } else if ($exists >= $need) {
                // Если весь заказанный товар в наличии, то подтверждаем его, остаток кладём на склад


                // меняем статус в корзине
                CartProduct::query()
                    ->whereIn('cart_id', $carts)
                    ->where('product_price_id', $product['product_price_id'])
                    ->each(function ($item) {
                        $item->update([
                            'status' => CartProductStatus::ORDER
                        ]);
                        event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::ORDER]));
                    });

                // Если заказанного товара частично или полностью нет, то работаем с этим
            } else {
                $lucky = 0;

                CartProduct::query()
                    ->whereIn('cart_id', $carts)
                    ->where('product_price_id', $product['product_price_id'])
                    ->orderBy('id', 'ASC')
                    ->each(function ($item) use (&$lucky, $exists) {

                        // Если корзине повезло, то добавляем товар в корзину
                        if ($lucky < $exists) {

                            // Если кол-во товара в корзине + то, что уже посчитали, меньше или равно наличию, то
                            // закидываем всё
                            if ($item->count + $lucky <= $exists) {
                                $lucky = $lucky + $item->count;

                                $item->update([
                                    'status' => CartProductStatus::ORDER
                                ]);
                                event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::ORDER]));

                                // Если же в корзине больше, чем пришло, то редактируем
                            } else {

                                $newCount = $exists - $lucky;
                                $clone = $item->replicate()->fill([
                                    'status' => CartProductStatus::ORDER,
                                    'count'  => $newCount
                                ]);

                                $item->update([
                                    'status' => CartProductStatus::CANCELED_PROVIDER,
                                    'extra'  => [
                                        ...($item->extra ?? []),
                                        'count_fact' => $newCount
                                    ]
                                ]);

                                $clone->save();

                                event(new SystemChangeStatusCartProductEvent($item, extra: [
                                    'status'     => CartProductStatus::CANCELED_PROVIDER,
                                    'count_fact' => $newCount
                                ]));

                                event(new SystemChangeStatusCartProductEvent($clone, extra: [
                                    'status'     => CartProductStatus::ORDER,
                                    'count_fact' => $newCount
                                ]));
                            }

                            // Из остальных корзин товар надо отменить
                        } else {
                            $item->update([
                                'status' => CartProductStatus::CANCELED_PROVIDER
                            ]);
                            event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CANCELED_PROVIDER]));
                        }
                    });
            }
        }

        if ($orders) {
            Order::query()
                ->whereIn('id', $orders)
                ->each(function ($item) {
                    if ($item->cart->products()->where('status', CartProductStatus::CONFIRM)->count()) return;
                    $item->update([
                        'system_status' => OrderSystemStatus::GET_FROM_PROVIDER
                    ]);
                    event(new SystemChangeSystemStatusOrderEvent($item, extra: ['system_status' => OrderSystemStatus::GET_FROM_PROVIDER]));
                });

            //dispatch(new SDGSendMatMasterJob()); вызываем из Inbound
            dispatch(new SDGSendInboundJob(extra: ['integration' => $integration]));
        }
    }
}