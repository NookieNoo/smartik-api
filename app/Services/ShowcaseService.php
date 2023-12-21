<?php

namespace App\Services;

use App\Enums\CartProductStatus;
use App\Enums\ProductActualSource;
use App\Events\System\SystemAddProductToActualEvent;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Order;
use App\Models\ProductActual;
use App\Models\ProductPrice;
use App\Services\Showcase\CartService;
use App\Services\Showcase\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ShowcaseService
{
    public function __construct (
        public ?CartService  $cart = null,
        public ?OrderService $order = null,
    )
    {
        $this->cart = new CartService();
        $this->order = new OrderService();
    }

    public static function deliveryAt (Carbon|string|null $date = null)
    {
        if (is_string($date)) $date = Carbon::parse($date);
        if (!$date) $date = now();

        if ((int)$date->format('H') >= 0 && (int)$date->format('H') < 24) {
            
            return $date->addDay();
        }
        return $date->addDays(2);
    }

    public function add (Collection|ProductPrice $prices, ProductActualSource $from = ProductActualSource::PRICE, int $count = 0, bool $hidden = false): void
    {
        if ($prices instanceof ProductPrice) $prices = collect([$prices]);

        $prices->each(function (ProductPrice $price) use ($from, $count, $hidden) {
            $count = $count ?: $price->count;

            $data = [
                'provider_id'       => $price->provider_id,
                'product_id'        => $price->product->id,
                'product_price_id'  => $price->id,
                'from_stock'        => $from === ProductActualSource::STOCK ? 1 : 0,
                'price'             => $price->price,
                'count'             => $count,
                'days_left'         => static::expireCalculate($price),
                'days_left_percent' => static::expirePercentCalculate($price),
                'discount'          => static::discountCalculate($price),
                'discount_percent'  => static::discountPercentCalculate($price),
                'hidden'            => $hidden
            ];

            $actual = $price->actual()
                ->where('provider_id', $data['provider_id'])
                ->where('product_id', $data['product_id'])
                ->where('product_price_id', $data['product_price_id'])
                ->where('from_stock', $data['from_stock'])
                ->first();

            if ($actual) {
                $actual->update(['count' => $actual->count + $data['count']]);
            } else {
                $actual = $price->actual()->create($data);
            }
            event(new SystemAddProductToActualEvent($actual, extra: ['from' => $from]));
            return $data;
        });
    }

    public function removeFromCarts (ProductActual $price): void
    {
        $products = CartProduct::query()
            ->where('product_price_id', $price->product_price_id)
            ->where('status', CartProductStatus::START)
            ->with('cart')
            ->get();

        $products->each(function ($item) {
            $item->update([
                'status' => CartProductStatus::CANCELED_ACTUAL
            ]);

            event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CANCELED_ACTUAL]));
        });
    }

    public function haveNeedAmount (ProductPrice $product_price, float $count): bool
    {
        if (!$product_price->actual || $product_price->actual->count < $count) {
            return false;
        }

        return true;
    }

    public static function expireCalculate (ProductPrice|Carbon $expired): int
    {
        if ($expired instanceof ProductPrice) {
            $expired = $expired->expired_at;
        }
        return Carbon::now()->diffInDays($expired);
    }

    public static function expirePercentCalculate (ProductPrice|Carbon $manufactured, ?Carbon $expired = null): int
    {
        if ($manufactured instanceof ProductPrice) {
            $expired = $manufactured->expired_at;
            $manufactured = $manufactured->manufactured_at;
        } else if ($expired === null) {
            throw new \InvalidArgumentException('expired cant be null');
        }
        return ceil($manufactured->diffInDays($expired) / 100 * static::expireCalculate($expired));
    }

    public static function discountCalculate (ProductPrice|float $start_price, ?float $price = null): int
    {
        if ($start_price instanceof ProductPrice) {
            $price = $start_price->price;
            $start_price = $start_price->start_price;
        } else if ($price === null) {
            throw new \InvalidArgumentException('price cant be null');
        }
        return (int)($start_price - $price);
    }

    public static function discountPercentCalculate (ProductPrice|float $start_price, ?float $price = null): int
    {
        if ($start_price instanceof ProductPrice) {
            $price = $start_price->price;
            $start_price = $start_price->start_price;
        } else if ($price === null) {
            throw new \InvalidArgumentException('price cant be null');
        }
        if (!$start_price) {
            throw new \InvalidArgumentException('price_start cant be 0');
        }
        return (int)(100 - ($price / $start_price * 100));
    }

    public function cart (Cart $cart): CartService
    {
        $this->cart->setCart($cart);
        return $this->cart;
    }

    public function order (Order $order): OrderService
    {
        $this->order->setOrder($order);
        return $this->order;
    }
}
