<?php

namespace App\Models;

use App\Data\CartCastData;
use App\Enums\CartStatus;
use App\Services\Showcase\CartService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Cart extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status'          => CartStatus::class,
        'prepayment_cast' => 'object',
        'final_cast'      => 'object'
    ];

    public function user (): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order (): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function products (): HasMany
    {
        return $this->hasMany(CartProduct::class);
        // wtf?
        // return $this->hasMany(CartProduct::class)->orderBy('id');
    }

    public function promos (): BelongsToMany
    {
        return $this->belongsToMany(Promo::class);
    }

    protected function sumProducts (): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getNewCartData('sumProducts')
        );
    }

    protected function sumFinal (): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getNewCartData('sumFinal')
        );
    }

    protected function deliveryPrice (): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getNewCartData('deliveryPrice')
        );
    }

    protected function promoDiscount (): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getNewCartData('promoDiscount')
        );
    }

    private function getNewCartData (string $attribute)
    {
        $data = $this->prepayment_cast;
        if ($this->final_cast) {
            $data = $this->final_cast;
        }

        if (!$data) return call_user_func([CartService::class, $attribute], $this);
        return $data->{$attribute};
    }

    public function cast (): CartCastData
    {
        $cartService = new CartService();
        $cartService->setCart($this);
        return $cartService->cast();
    }
}