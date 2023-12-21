<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class ProductFullResource extends JsonResource
{
    public function toArray ($request)
    {
        return [
            ...$this->only([
                'uuid',
                'name',
                'description',
                'compound'
            ]),
            'energy'   => new ProductEnergyResource($this->energy),
            'prices'   => $this->actuals->map(fn ($item) => [
                'uuid'              => $item->product_price->uuid,
                'price'             => $item->price,
                'price_old'         => $item->product_price->start_price,
                'count'             => $item->count,
                'days_left'         => $item->days_left,
                'days_left_percent' => $item->days_left_percent,
                'discount'          => $item->discount,
                'discount_percent'  => $item->discount_percent,
                'manufactured_at'   => $item->product_price->manufactured_at?->format('Y-m-d'),
                'expired_at'        => $item->product_price->expired_at?->format('Y-m-d'),
            ]),
            'catalogs' => array_unique(array_merge($this->first()->catalogs->pluck('uuid')->toArray(), ...$this->first()->catalogs->map(function ($catalog) {
                return $catalog->ancestors->pluck('uuid');
            })->toArray()))
        ];
    }
}
