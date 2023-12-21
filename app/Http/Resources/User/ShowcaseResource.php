<?php

namespace App\Http\Resources\User;

use App\Services\ShowcaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowcaseResource extends JsonResource
{
    public function toArray ($request)
    {
        return [
            'uuid'          => $this->first()->product->uuid,
            'name'          => $this->first()->product->name,
            'weight'        => $this->first()->product->weight,
            'weight_type'   => $this->first()->product->weight_type?->title(),
            'prices'   => $this->map(fn ($item) => [
                'uuid'             => $item->product_price->uuid,
                'price'            => $item->price,
                'from_stock'       => $item->from_stock,
                'price_old'        => $item->product_price->start_price,
                'count'            => $item->count,
                'discount_percent' => ShowcaseService::discountPercentCalculate($item->product_price),
                'expired_at'       => $item->product_price->expired_at?->format('Y-m-d'),
            ]),
            'catalogs' => array_unique(array_merge($this->first()->catalogs->pluck('uuid')->toArray(), ...$this->first()->catalogs->map(function ($catalog) {
                return $catalog->ancestors->pluck('uuid');
            })->toArray()))
        ];
    }
}
