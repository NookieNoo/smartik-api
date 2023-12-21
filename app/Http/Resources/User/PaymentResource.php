<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class PaymentResource extends JsonResource
{
    public function toArray ($request)
    {
        return [
            ...$this->only([
                'uuid',
                'payment_system',
                'payment_method',
                'sum',
                'status',
                'extra'
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
