<?php

namespace App\Models;

use App\Enums\CartProductStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartProduct extends Model
{

    protected $casts = [
        'price'      => 'float',
        'count'      => 'float',
        'from_stock' => 'boolean',
        'status'     => CartProductStatus::class,
        'extra'      => 'json'
    ];

    public function cart (): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product (): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function product_price (): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    public static function holdByOrder (Order|int $order)
    {
        if (is_int($order)) {
            $order = Order::find($order);
        }

        $order->cart->products()->where('status', CartProductStatus::START)->each(function ($item) {
            $item->update([
                'status' => CartProductStatus::CONFIRM
            ]);
            event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CONFIRM]));
        });
    }

    public static function holdByProductPrice (ProductPrice|int $product)
    {
        if (is_int($product)) {
            $product = ProductPrice::find($product);
        }


        CartProduct::where('status', CartProductStatus::START)->where('product_price_id', $product->id)->each(function ($item) {
            $item->update([
                'status' => CartProductStatus::CONFIRM
            ]);
            event(new SystemChangeStatusCartProductEvent($item, extra: ['status' => CartProductStatus::CONFIRM]));
        });
    }
}