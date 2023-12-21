<?php

namespace App\Models;

use App\Enums\ProductPriceSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductPrice extends Model
{
    public $timestamps = false;

    protected $casts = [
        'source'          => ProductPriceSource::class,
        'price'           => 'float',
        'start_price'     => 'float',
        'finish_price'    => 'float',
        'date'            => 'date',
        'manufactured_at' => 'datetime',
        'expired_at'      => 'datetime',
        'soldout_at'      => 'datetime',
    ];

    public function product (): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function provider (): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function actual (): HasOne
    {
        return $this->hasOne(ProductActual::class);
    }

    public function cart_products (): HasMany
    {
        return $this->hasMany(CartProduct::class, 'product_price_id');
    }
}