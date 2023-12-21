<?php

namespace App\Http\Resources\User;

use App\Enums\CartProductStatus;
use App\Services\Showcase\CartService;
use App\Services\ShowcaseService;
use Illuminate\Http\Resources\Json\JsonResource;


class CartResource extends JsonResource
{
    public function toArray ($request)
    {
        $cast = $this->cast();
        return [
            'delivery'       => [
                'possible' => CartService::$sum_minimal,
                'free'     => CartService::$sum_free_delivery,
                'price'    => CartService::$delivery_price,
            ],
            'delivery_at'    => ShowcaseService::deliveryAt($this->created_at)->format('Y-m-d'),
             'delivery_time'  => CartService::$time_delivery,
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
            'sum_products'   => $cast->sumProducts,
            'delivery_price' => $cast->deliveryPriceFinal,
            'promo_discount' => $cast->promoDiscount,
            'sum_final'      => $cast->sumFinal,
            'promos'         => PromoResource::collection($this->promos),
            'products'       => $this->products->map(function ($item) use ($cast) {
                $price = $cast->products->toCollection()->where('id', $item->product_price->id)->first()?->price ?? $item->price;
                return [
                    'uuid'              => $item->product->uuid,
                    'uuid_price'        => $item->product_price->uuid,
                    'name'              => $item->product->name,
                    'price'             => $price,
                    'count'             => $item->count,
                    'limit'             => $item->product_price->actual->count ?? 0,
                    'discount'          => ShowcaseService::discountCalculate($item->product_price->start_price, $price),
                    'discount_percent'  => ShowcaseService::discountPercentCalculate($item->product_price->start_price, $price),
                    'days_left'         => ShowcaseService::expireCalculate($item->product_price),
                    'days_left_percent' => ShowcaseService::expirePercentCalculate($item->product_price),
                    'is_deleted'        => CartProductStatus::isCanceled($item->status),
                    'expired_at'        => $item->product_price->expired_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
}
