<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class UserPromoResource extends JsonResource
{
    public function toArray ($request)
    {
        return [
            ...$this->only([
                'uuid',
            ]),
            'promo'      => new PromoResource($this->whenLoaded('promo')),
            'used_at'    => $this->used_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
