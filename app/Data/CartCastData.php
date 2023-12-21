<?php

namespace App\Data;

use App\Data\Casts\PriceCast;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CartCastData extends Data
{
    public function __construct (
        #[WithCast(PriceCast::class)]
        public float          $sumProducts,
        #[WithCast(PriceCast::class)]
        public float          $sumProductsWithoutPromo,
        #[WithCast(PriceCast::class)]
        public float          $sumProductsAll,
        #[WithCast(PriceCast::class)]
        public float          $deliveryPrice,
        #[WithCast(PriceCast::class)]
        public float          $deliveryPriceFinal,
        public bool           $deliveryCancelByPromo,
        public bool           $deliveryCancelByLogic,
        public bool           $deliveryCancelByCanceled,
        #[WithCast(PriceCast::class)]
        public float          $promoDiscount,
        #[WithCast(PriceCast::class)]
        public float          $sumFinal,
        #[WithCast(PriceCast::class)]
        public float          $sumCanceled,
        #[DataCollectionOf(CartCastProductData::class)]
        public DataCollection $products
    ) {}
}