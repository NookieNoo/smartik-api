<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class PromoResource extends JsonResource
{
    public function toArray ($request)
    {
        return $this->only([
            'name',
            'active',
            'code',
            'type',
            'discount',
            'from_sum',
            'ended_at'
        ]);
    }
}
