<?php

namespace App\Http\Controllers\User;

use App\Enums\CartStatus;
use App\Events\User\UserChangeCartEvent;
use App\Events\User\UserRemoveCartEvent;
use App\Events\User\UserRemoveOrderWithCartEvent;
use App\Exceptions\Custom\CantAddToCartException;
use App\Exceptions\Custom\CartIsNullException;
use App\Exceptions\Custom\CartProductCountException;
use App\Exceptions\Custom\PromocodeException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\User\UserChangeCountCartRequest;
use App\Http\Requests\User\UserPromocodeCartRequest;
use App\Http\Requests\User\UserRemoveCartProductRequest;
use App\Http\Requests\User\UserUpserCartProductRequest;
use App\Http\Resources\User\CartResource;
use App\Models\Order;
use App\Models\ProductPrice;
use App\Models\Promo;
use App\Models\User;
use App\Models\UserPromo;
use App\Services\ActiveApi\Attributes\Title;
use App\Services\Showcase\CartService;
use App\Services\ShowcaseService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

#[
    Title('Корзина', 'cart'),
]
class CartController extends ApiController
{
    protected array $relations = [
        'products' => [
            'product',
            'product_price' => ['actual']
        ]
    ];

    #[
        Title('Инфо', 'info'),
    ]
    public function info ()
    {
        $cart = $this->user->cart()->with($this->relations)->first();

        if (!$cart) {
            return $this->send(null);
        }

        return $this->send(new CartResource($cart));
    }

    #[
        Title('Очистить корзину', 'clear'),
    ]
    public function clear ()
    {
        $cart = $this->user->cart;
        if ($cart && $cart->status === CartStatus::ACTIVE) {
            $order = Order::where('cart_id', $cart->id)->first();
            if ($order) {
                $order->delete();
                event(new UserRemoveOrderWithCartEvent);
            }
            $cart->delete();
            event(new UserRemoveCartEvent);
        }
        return $this->send(true);
    }

    #[
        Title('Добавить/изменить продукт', 'upsert'),
    ]
    public function upsert (UserUpserCartProductRequest $request, ShowcaseService $showcase)
    {
        $cart = $this->user->cart;
        if (!$cart) {
            $cart = $this->user->cart()->create();
        }
        $cart->load($this->relations);

        $product_price = ProductPrice::where('uuid', $request->input('product_price'))->first();
        $count = $request->input('count');

        if ($count == 0) {
            $showcase->cart($cart)->removeItem($product_price);
            return $this->send(new CartResource($cart));
        }

        if (!$showcase->haveNeedAmount($product_price, $count)) {
            throw new CantAddToCartException;
        }

        $showcase->cart($cart)->upsertItem($product_price, $count);
        $cart->refresh();

        // обновляем сумму в заказе
        $cast = $cart->cast();
        Order::where('cart_id', $cart->id)->update([
            'sum_final'    => $cast->sumFinal,
            'sum_products' => $cast->sumProducts
        ]);

        event(new UserChangeCartEvent([
            'request' => $request->only(['product_price', 'count']),
            'cart_id' => $cart->id
        ]));

        return $this->send(new CartResource($cart));
    }

    #[
        Title('Удалить продукт', 'remove'),
    ]
    public function remove (UserRemoveCartProductRequest $request, ShowcaseService $showcase)
    {
        $cart = $this->user->cart;
        if (!$cart) {
            return $this->send(null);
        }
        $cart->load($this->relations);
        $product_price = ProductPrice::where('uuid', $request->input('product_price'))->first();
        $showcase->cart($cart)->removeItem($product_price);

        if (!$cart->products()->count()) {
            $showcase->cart::cancel($cart);
            return $this->send(null);
        }

        return $this->send(new CartResource($cart->refresh()));
    }

    #[
        Title('Промокод', 'promo'),
    ]
    public function promo (UserPromocodeCartRequest $request, ShowcaseService $showcase)
    {
        $cart = $this->user->cart;
        if (!$cart) throw new CartIsNullException;

        /*
         * Обернём в трай/кэч для лога дебага
         */
        try {
            /*
             * Проверяем промик, а так же проверяем, есть ли персональный такой. В случае, если персональный
             * есть, но ни разу не применялся, мы его создаём в таблице promos, чтобы далее юзать его из таблицы
             * promos.
             * Формат создания определяется в модели пользователя в методе checkPersonalPromo
             */
            $promo = Promo::where('code', $request->input('code'))->first();
            if (!$promo) {
                $personal = User::checkPersonalPromo($request->input('code'));
                if (!$personal || $personal['user_id'] === $this->user->id) throw new PromocodeException('not exist');
                $promo = Promo::create($personal['promo']);
                $promo->attachTag('Referal Program', 'promo');
            }

            /*
             * Проверяем правила применения промо и его тегов, только правила применения
             */

            Gate::inspect('use', $promo);
            Gate::inspect('use_tags', $promo);

            if (!$promo->getTagsBuff()->disable_minimum_sum && $promo->from_sum && $promo->from_sum > $showcase->cart($cart)->cast()->sumProductsWithoutPromo) {
                throw new PromocodeException('no sum', ['need' => (float)number_format($promo->from_sum - $showcase->cart($cart)->cast()->sumProductsWithoutPromo, 2, '.', '')]);
            }
        } catch (\Throwable $e) {
            Log::debug('promo error', [
                'user'  => $this->user->id,
                'cart'  => $cart->id,
                'promo' => $promo?->id ?? $request->input('code'),
                'data'  => $e->data ?? null
            ]);
            throw $e;
        }

        Log::debug('promo ok', [
            'user'  => $this->user->id,
            'cart'  => $cart->id,
            'promo' => $promo->id
        ]);


        $showcase->cart($cart)->setPromo($promo);
        $cart->load($this->relations);

        return $this->send(new CartResource($cart->refresh()));
    }

    #[
        Title('Убрать промокод', 'remove_promo'),
    ]
    public function removePromo (ShowcaseService $showcase)
    {
        $cart = $this->user->cart;
        if (!$cart) {
            throw new CartIsNullException;
        }
        $showcase->cart($cart)->setPromo(null);
        $cart->load($this->relations);

        return $this->send(new CartResource($cart->refresh()));
    }

    #[
        Title('Товар в корзину', 'change_count'),
    ]
    public function changeCount (UserChangeCountCartRequest $request, ProductPrice $product_price, ShowcaseService $showcase)
    {
        $cart = $this->user->cart;
        if (!$cart) {
            $cart = $this->user->cart()->create();
        }
        $showcase->cart($cart)->changeProductCount($product_price, $request->input('count'));
        $cart->load($this->relations);

        return $this->send(new CartResource($cart->refresh()));
    }

    public function check (CartService $cartService)
    {
        if (!$this->user->cart) {
            return $this->send(true);
        }
        $cartService->setCart($this->user->cart);
        if ($errors = $cartService->error()) {
            throw new CartProductCountException($errors);
        }
        return $this->send(true);
    }
}
