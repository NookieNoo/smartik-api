<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class MinimalOrderResource extends JsonResource
{
    public function toArray ($request)
    {
        return [
            ...$this->only([
                'uuid',
                'status',
                'name'
            ]),
            'sum_final' => $this->cart?->cast()->sumFinal ?? 0
        ];
    }
}
