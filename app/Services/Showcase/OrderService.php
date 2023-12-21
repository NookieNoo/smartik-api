<?php

namespace App\Services\Showcase;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductActualSource;
use App\Events\Admin\AdminChangeStatusOrderEvent;
use App\Events\System\SystemChangeStatusOrderEvent;
use App\Events\System\SystemMinusCountAfterHoldOrderEvent;
use App\Events\System\SystemRemoveItemOnOrderHoldProductEvent;
use App\Events\System\SystemSoldOutAfterHoldOrderEvent;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Models\Admin;
use App\Models\CartProduct;
use App\Models\Order;
use App\Models\User;
use App\Services\ShowcaseService;
use Carbon\Carbon;

class OrderService
{
    protected Order $order;

    public function setOrder (Order $order): void
    {
        $this->order = $order;
    }

    public function cancel (OrderStatus $status = OrderStatus::CANCELED_MANAGER): void
    {
        if (in_array($this->order->status, OrderStatus::canceled())) return;
        $this->order->status = $status;
        $event = match (auth()->user() ? get_class(auth()->user()) : null) {
            User::class  => UserChangeStatusOrderEvent::class,
            Admin::class => AdminChangeStatusOrderEvent::class,
            default      => SystemChangeStatusOrderEvent::class
        };
        event(new $event($this->order, auth()->user(), ['status' => $status]));
        $this->order->save();
    }

    public function holdProducts (): void
    {
        $this->order->cart->products->each(function (CartProduct $item) {
            $product_price = $item->product_price;
            $count = $item->count;

            if (!$product_price->actual?->count) {
                event(new SystemRemoveItemOnOrderHoldProductEvent($this->order, extra: [
                    'product_price_id' => $product_price->id,
                ]));
                return;
            }

            $newCount = $product_price->actual->count - $count;

            if ($newCount <= 0) {
                $product_price->actual->delete();
                $product_price->soldout_at = Carbon::now();
                $product_price->save();
                event(new SystemSoldOutAfterHoldOrderEvent($this->order, extra: [
                    'product_price_id' => $product_price->id,
                ]));
            } else {
                $product_price->actual->count = $newCount;
                $product_price->actual->save();
                event(new SystemMinusCountAfterHoldOrderEvent($this->order, extra: [
                    'product_price_id' => $product_price->id,
                    'user_count'       => $count,
                    'new_count'        => $newCount
                ]));
            }
            if ($item->from_stock) {
                $item->update(['status' => CartProductStatus::WAREHOUSE]);
            }
        });
    }

    public function unHoldProducts (): void
    {
        $showcase = new ShowcaseService();
        $this->order->cart->products()->whereIn('status', [
            CartProductStatus::WAREHOUSE,
            CartProductStatus::DELIVERY
        ])->each(function (CartProduct $item) use ($showcase) {
            $product_price = $item->product_price;
            $count = $item->count;

            $showcase->add($product_price, ProductActualSource::STOCK, $count);
        });
    }
}