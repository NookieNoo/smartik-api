<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray ($request)
    {
        return array_merge(
            $this->only(['uuid', 'name', 'sex']),
            [
                'phone'               => $this->phone?->value,
                'phone_verified_at'   => $this->phone?->extra->verified_at,
                //'email'          => $this->email?->value,
                //'email_verified' => $this->email?->extra->verified_at,
                'email'               => $this->email,
                'email_verified_at'   => true,
                'birthday_at'         => $this->birthday_at?->format('Y-m-d'),
                'cart'                => new CartResource($this->whenLoaded('cart')),
                'orders'              => MinimalOrderResource::collection($this->whenLoaded('lastOrders')),
                'addresses'           => UserAddressResource::collection($this->whenLoaded('addresses')),
                'personal_promocodes' => $this->system_name ? [
                    'delivery' => 'DLR' . $this->system_name,
                    'money'    => 'MNY' . $this->system_name,
                ] : null,
                'media_source' => $this->mediaSource
            ],
            $this->additional
        );
    }
}
