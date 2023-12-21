<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status'             => OrderStatus::class,
        'sum_products'       => 'float',
        'delivery_price'     => 'float',
        'promo_discount'     => 'float',
        'sum_final'          => 'float',
        'delivery_at'        => 'datetime',
        'delivery_change_at' => 'datetime',
        'delivered_at'       => 'datetime',
        'in_work'            => 'boolean',
        'extra'              => 'json'
    ];

    public function user (): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address (): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function cart (): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function cart_products (): HasManyThrough
    {
        return $this->hasManyThrough(CartProduct::class, Cart::class);
    }

    public function payment (): HasOne
    {
        return $this->hasOne(Payment::class)->whereNotIn('status', PaymentStatus::canceled())->latestOfMany();
    }

    public function payments (): HasMany
    {
        return $this->hasMany(Payment::class)->whereNotIn('status', PaymentStatus::canceled());
    }

    public function checks (): HasMany
    {
        return $this->hasMany(KkmCheck::class);
    }

    public function delivery (): HasOne
    {
        return $this->hasOne(OrderDelivery::class)->latestOfMany();
    }

    public function hasFrozenProducts(): bool
    {
        return (bool) $this->cart?->products->some(function(CartProduct $cartProduct) {
            return $cartProduct->product->is_frozen;
        });
    }

    public function productMarks(): HasMany
    {
        return $this->hasMany(ProductMark::class);
    }
}
