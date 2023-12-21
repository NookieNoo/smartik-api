<?php

namespace App\Data;

use App\Data\Casts\PriceCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class CartCastProductData extends Data
{
    public function __construct (
        public string       $name,
        public int          $vat,
        #[WithCast(PriceCast::class)]
        public float        $price,
        #[WithCast(PriceCast::class)]
        public float        $priceWithoutPromo,
        #[WithCast(PriceCast::class)]
        public float        $count,
        public int|Optional $id,
        public bool         $canceled,
        public int          $product_id,
    ) {}
}
