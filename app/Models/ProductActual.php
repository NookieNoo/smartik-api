<?php

namespace App\Models;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Jobs\SDG\SDGSendOutboundJob;
use App\Services\Showcase\CartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class ProductActual extends Model
{
    use HasRelationships;

    protected $casts = [
        'price'             => 'float',
        'count'             => 'float',
        'from_stock'        => 'boolean',
        'discount'          => 'int',
        'discount_percent'  => 'int',
        'days_left'         => 'int',
        'days_left_percent' => 'int',
        'hidden'            => 'boolean',
        'expired_at'        => 'datetime',
    ];

    public function product (): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function provider (): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function product_price (): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    public function catalogs (): HasManyDeep
    {
        //return $this->hasManyDeep(Catalog::class, [Product::class, 'product_catalog'], ['id1', 'id2', 'id3'], ['id4', 'id5', 'id6']);
        return $this->hasManyDeepFromRelations($this->product(), (new Product())->catalogs());
    }

    public static function removeUnusedTodayByProvider (Provider|int $provider)
    {
        if (!is_int($provider)) {
            $provider = $provider->id;
        }

        ProductActual::withoutGlobalScopes()
            ->where('provider_id', $provider)
            ->where('from_stock', false)
            ->get()
            ->each(function ($item) {
                $products = CartProduct::where('product_price_id', $item->product_price)
                    ->with(['cart' => ['order']])
                    ->get();

                $products->each(function ($item) {
                    $item->update(['status' => CartProductStatus::CANCELED_ACTUAL]);
                    event(new SystemChangeStatusCartProductEvent($item, extra: [
                        'status' => CartProductStatus::CANCELED_ACTUAL,
                        'cause'  => 'reload provider prices'
                    ]));
                });

                $products->whereNotNull('cart.order.id')->pluck('cart.order')->unique('id')->values()->each(function ($order) {
                    $cast = $order->cart->cast();
                    $order->update([
                        'sum_products' => $cast->sumProducts,
                        'sum_final'    => $cast->sumFinal,
                    ]);

                    if ($order->status === OrderStatus::PAYMENT_DONE && !$order->cart->products()->where('from_stock', false)->whereNotIn('status', CartProductStatus::canceled())->count()) {
                        dispatch(new SDGSendOutboundJob(extra: $order));
                    }
                });

                $item->delete();
            });
    }


    protected static function booted ()
    {
        static::addGlobalScope('hidden', function (Builder $builder) {
            $builder->whereHidden(false);
        });
    }
}