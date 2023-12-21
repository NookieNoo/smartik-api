<?php

namespace App\Services\Showcase;

use App\Data\CartCastData;
use App\Data\CartCastProductData;
use App\Enums\CartProductStatus;
use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PromocodeType;
use App\Exceptions\Custom\CartProductCountException;
use App\Exceptions\Custom\NoActualCountException;
use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Order;
use App\Models\ProductPrice;
use App\Models\Promo;
use App\Models\User;
use App\Services\ShowcaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class CartService
{
    protected Cart      $cart;
    public static array $time_finish   = [15, 0];
    public static array $time_delivery_old = [9, 21];
    public static array $time_delivery = [[9, 15], [15, 21]];
    protected ?User     $user          = null;

    public static int $sum_minimal       = 500;
    public static int $sum_free_delivery = 999;
    public static int $delivery_price    = 99;

    public function __construct ()
    {
        $user = auth()->user();
        if ($user && get_class($user) === User::class) {
            $this->user = auth()->user();
        }
    }

    public function setCart (Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function setPromo (?Promo $promo = null): void
    {
        $this->cart->promos()->detach();
        if ($promo) {
            $this->cart->promos()->attach($promo);
        }
    }

    public function changeProductCount (ProductPrice $price, float $count): void
    {
        if ($count > 0 && ($price->actual->count ?? 0) < $count) {
            throw new NoActualCountException($price->actual->count ?? 0);
        }
        if ($count) {
            $this->cart->products()->updateOrCreate([
                'product_price_id' => $price->id
            ], [
                'product_id' => $price->product_id,
                'from_stock' => $price->actual->from_stock,
                'count'      => $count,
                'price'      => $price->price
            ]);
        } else {
            $this->cart->products()->where('product_price_id', $price->id)->delete();
        }
    }

    public function cast ()
    {
        $cast = CartCastData::from([
            'sumProducts'              => 0,
            'sumProductsWithoutPromo'  => 0,
            'sumProductsAll'           => 0,
            'deliveryPrice'            => static::$delivery_price,
            'deliveryPriceFinal'       => static::$delivery_price,
            'deliveryCancelByCanceled' => false,
            'deliveryCancelByPromo'    => false,
            'deliveryCancelByLogic'    => false,
            'promoDiscount'            => 0,
            'sumFinal'                 => 0,
            'sumCanceled'              => 0,
            'products'                 => []
        ]);

        $products = $this->cart->products;
        $promo = $this->cart->promos()->first();

        $cast->products = CartCastProductData::collection($products->map(function (CartProduct $product) {
            return CartCastProductData::from([
                'name'              => $product->product?->name ?? 'позиция удалена',
                'vat'               => $product->product?->vat ?? 0,
                'priceWithoutPromo' => $product->price,
                'price'             => $product->price,
                'id'                => $product->product_price_id,
                'count'             => $product->count,
                'canceled'          => in_array($product->status, [
                    CartProductStatus::CANCELED_ACTUAL,
                    CartProductStatus::CANCELED_PROVIDER,
                    CartProductStatus::CANCELED_SDG,
                    CartProductStatus::CANCELED_USER,
                ]),
                'product_id'        => $product->product_id
            ]);
        }));

        $cast->sumProductsAll = $cast->products
            ->map(fn (CartCastProductData $item) => $item->count * $item->price)
            ->toCollection()
            ->sum();

        $cast->sumProducts = $cast->products
            ->map(fn (CartCastProductData $item) => $item->canceled ? 0 : $item->count * $item->price)
            ->toCollection()
            ->sum();

        $cast->sumProductsWithoutPromo = $cast->sumProducts;

        $cast->sumCanceled = $cast->products
            ->map(fn (CartCastProductData $item) => $item->canceled ? $item->count * $item->price : 0)
            ->toCollection()
            ->sum();

        if ($promo) {
            $buff = $promo->getTagsBuff();

            switch ($promo->type) {
                case PromocodeType::DELIVERY:
                {
                    $cast->deliveryPriceFinal = 0;
                    $cast->promoDiscount = static::$delivery_price;
                    $cast->deliveryCancelByPromo = true;
                    break;
                }
                case PromocodeType::VALUE:
                case PromocodeType::PERCENT:
                {
                    if ($promo->type === PromocodeType::VALUE) {
                        $cast->promoDiscount = $promo->discount;
                    } else {
                        $cast->promoDiscount = $cast->sumProducts * ($promo->discount / 100);
                    }

                    // Процент скидки для каждого продукта
                    $percentRatio = ($cast->sumProductsWithoutPromo == 0) ? 0 : round($cast->promoDiscount / $cast->sumProductsWithoutPromo, 4);

                    $cast->products = $cast->products->map(function (CartCastProductData $item) use ($percentRatio) {
                        if ($item->canceled) return $item;

                        $item->price = round($item->priceWithoutPromo * (1 - $percentRatio), 2);
//                        $item->price = $item->price - ($productDiscount / $item->count);
//                        if ($finalDiscount > 0 && ($item->price * $item->count) > $finalDiscount) {
//                            $item->price = $item->price - ($finalDiscount / $item->count);
//                            $finalDiscount = 0;
//                        }
                        return $item;
                    });
//                    $countProducts = $cast->products
//                        ->toCollection()
//                        ->filter(fn (CartCastProductData $item) => !$item->canceled)
//                        ->count();
//
//                    $productDiscount = floor($cast->promoDiscount / ($countProducts ?: 1));
//                    $finalDiscount = $cast->promoDiscount - ($productDiscount * $countProducts);
//
//                    $cast->products = $cast->products->map(function (CartCastProductData $item) use (&$finalDiscount, $productDiscount) {
//                        if ($item->canceled) return $item;
//
//                        $item->price = $item->price - ($productDiscount / $item->count);
//                        if ($finalDiscount > 0 && ($item->price * $item->count) > $finalDiscount) {
//                            $item->price = $item->price - ($finalDiscount / $item->count);
//                            $finalDiscount = 0;
//                        }
//                        return $item;
//                    });
                    break;
                }
            }

            if ($buff->disable_delivery) {
                $cast->deliveryPriceFinal = 0;
                $cast->promoDiscount = static::$delivery_price;
                $cast->deliveryCancelByPromo = true;
            }

            $cast->sumProducts = $cast->products
                ->map(fn (CartCastProductData $item) => $item->canceled ? 0 : $item->count * $item->price)
                ->toCollection()
                ->sum();
        }

        if ($cast->sumProductsAll >= static::$sum_free_delivery) {
            $cast->deliveryPriceFinal = 0;
            $cast->deliveryCancelByLogic = true;
            if ($this->cart->created_at > "2023-11-22 12:30:01") {
                if ($cast->sumProducts < static::$sum_free_delivery) {
                    $cast->deliveryPriceFinal = $cast->deliveryPrice;
                    $cast->deliveryCancelByLogic = false;
                }
            }
        }
        if ($cast->products->filter(fn (CartCastProductData $item) => $item->canceled)->count()) {
            $cast->deliveryPriceFinal = 0;
            $cast->deliveryCancelByCanceled = true;
            if ($this->cart->created_at > "2023-11-22 12:30:01") {
                if (($cast->sumProducts < static::$sum_free_delivery) && !$cast->deliveryCancelByPromo) {
                    $cast->deliveryPriceFinal = $cast->deliveryPrice;
                    $cast->deliveryCancelByCanceled = false;
                }
            }
        }

        $cast->sumFinal = $cast->sumProducts;

        //@FIXME Временный костыль
        if ($this->cart->created_at > "2023-11-06 14:09:01") {
            if ($cast->deliveryCancelByCanceled && !$cast->deliveryCancelByPromo && $cast->sumProductsAll < static::$sum_free_delivery) {
                $cast->sumFinal = $cast->sumFinal + $cast->deliveryPrice;
                $cast->deliveryPriceFinal = $cast->deliveryPrice;
            }
        }

        if (!$cast->deliveryCancelByCanceled && !$cast->deliveryCancelByLogic && !$cast->deliveryCancelByPromo) {
            $cast->sumFinal = $cast->sumFinal + $cast->deliveryPrice;
        }

        if ($cast->deliveryCancelByCanceled && !$cast->deliveryCancelByLogic && !$cast->deliveryCancelByPromo) {
            $cast->sumCanceled = $cast->sumCanceled + $cast->deliveryPrice;
        }

        // ещё раз оборачиваем, чтобы привести касты
        return CartCastData::from($cast);
    }

    public function move (User|Cart $to): Cart
    {
        if ($to instanceof Cart) {
            $to = $to->user;
        }

        // удаляем корзину пользователя, в которого переносим (вдруг она есть)
        if ($to->cart) static::cancel($to->cart, CartStatus::CANCELED_REPLACE);

        $this->cart->update([
            'user_id' => $to->id,
            'status'  => CartStatus::ACTIVE,
        ]);

        try {
            Gate::forUser($to)->inspect('use', $this->cart->promos()->last());
            Gate::forUser($to)->inspect('use_tags', $this->cart->promos()->last());
        } catch (\Throwable $e) {
            $this->setPromo(null);
        }


        // удаляем старую корзину
        //static::cancel($this->cart, CartStatus::CANCELED_REPLACE);

        return $this->cart;
    }

    public function upsertItem (ProductPrice $product_price, float $count): void
    {
        CartProduct::upsert([
            'cart_id'          => $this->cart->id,
            'product_id'       => $product_price->product->id,
            'product_price_id' => $product_price->id,
            'from_stock'       => $product_price->actual->from_stock,
            'count'            => $count,
            'price'            => $product_price->actual->price
        ], ['cart_id', 'product_price_id']);
    }

    public function removeItem (ProductPrice $product_price): void
    {
        CartProduct::where('cart_id', $this->cart->id)->where('product_price_id', $product_price->id)->delete();
    }

    public static function cancel (Cart $cart, CartStatus $status = CartStatus::CANCELED_USER): void
    {
        $cart->update([
            'status' => $status
        ]);
        $cart->delete();
    }

    public static function sumProducts (Cart $cart)
    {
        $sum = $cart->products()->whereNotIn('status', CartProductStatus::canceled())->get()->sum(function ($product) {
            return number_format($product->product_price->price * $product->count, 2, '.', '');
        });
        return $sum;
    }

    public static function deliveryPrice (Cart $cart, $reverse = false)
    {
        $freeDelivery = $cart->promos()->where('type', PromocodeType::DELIVERY)->count();
        if ($freeDelivery) return 0;
        $price = static::sumProducts($cart) >= static::$sum_free_delivery ? 0 : static::$delivery_price;

        if (in_array($cart->order?->status, [OrderStatus::PAYMENT_DONE, OrderStatus::DONE])) {
            $canceled = $cart->products()->whereIn('status', [
                CartProductStatus::CANCELED_ACTUAL,
                CartProductStatus::CANCELED_PROVIDER,
                CartProductStatus::CANCELED_SDG
            ])->count();
            if ($canceled) {
                if ($price) {
                    $price = $reverse ? static::$delivery_price : 0;
                }
            }
        }
        return $price;
    }

    public static function promoDiscount (Cart $cart)
    {
        $promo = $cart->promos()->first();
        if (!$promo || ($promo->from_sum && $promo->from_sum > static::sumProducts($cart))) {
            return 0;
        }
        if ($promo->type === 'percent') {
            return number_format(static::sumProducts($cart) / 100 * $promo->discount, 2, '.', '');
        }
        return $promo->discount;
    }

    public static function sumFinal (Cart $cart)
    {
        return static::sumProducts($cart) + static::deliveryPrice($cart) - static::promoDiscount($cart);
    }

    public static function getDeliveryWindowFrom (int $slot): string
    {
        $deliveryDay = ShowcaseService::deliveryAt();
        return $deliveryDay->setHour(self::$time_delivery[$slot][0])->set('second', 0)->set('minute', 0)->format('YmdHis');
    }

    public static function getDeliveryWindowTo (int $slot): string
    {
        $deliveryDay = ShowcaseService::deliveryAt();
        return $deliveryDay->setHour(self::$time_delivery[$slot][1])->set('second', 0)->set('minute', 0)->format('YmdHis');
    }

    public function error (): false|array
    {
        $error = false;
        foreach ($this->cart->products as $product) {
            if ($product->count > ($product->product_price->actual?->count ?? 0)) {
                if (!$error) {
                    $error = [];
                }
                $error[] = [
                    'product_id'         => $product->product_id,
                    'product_uuid'       => $product->product->uuid,
                    'product_price_id'   => $product->product_price_id,
                    'product_price_uuid' => $product->product_price->uuid,
                    'count'              => $product->product_price->actual?->count ?? 0
                ];
            }
        }
        if (static::sumProducts($this->cart) < static::$sum_minimal) {
            $promo = $this->cart->promos()->first();
            $buff = $promo?->getTagsBuff();
            if (!$buff || !$buff->disable_minimum_sum) {
                $error = ['minimal'];
            }
        }
        if (!$this->user) {
            $error = ['unauth'];
        }
        return $error;
    }

    public function publish (?string $comment = null, ?int $time_delivery_at = 1): Order
    {
        if ($errors = $this->error()) {
            throw new CartProductCountException($errors);
        }

        $address = $this->user->addresses->first();
        $extra = [
            'address' => $address?->toArray() ?? []
        ];

        $cast = $this->cart->cast();

        $order = Order::create([
            'user_id'               => $this->user->id,
            'cart_id'               => $this->cart->id,
            'user_address_id'       => $address?->id ?? 0,
            'user_payment_id'       => 1,
            'sum_products'          => $cast->sumProducts,
            'delivery_price'        => $cast->deliveryPriceFinal,
            'promo_discount'        => $cast->promoDiscount,
            'sum_final'             => $cast->sumFinal,
            'comment'               => $comment,
            'extra'                 => $extra,
            'delivery_at'           => ShowcaseService::deliveryAt(),
            'time_delivery_slot'    => $time_delivery_at ?? 1,
        ]);

        return $order;
    }
}
