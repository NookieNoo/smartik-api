<?php

namespace App\Http\Resources\User;

use App\Models\UserAddress;
use App\Services\Showcase\CartService;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderResource extends JsonResource
{
    public function toArray ($request)
    {
        $cast = $this->cart?->cast();
        return [
            ...$this->only([
                'uuid',
                'status',
                'comment',
                'done_at',
            ]),
            'sum_products'       => $cast->sumProducts ?? 0,
            'delivery_price'     => $cast->deliveryPriceFinal ?? 0,
            'promo_discount'     => $cast->promoDiscount ?? 0,
            'sum_final'          => $cast->sumFinal ?? 0,
            'name'               => (string)$this->name,
            'delivery_at'        => $this->delivery_at?->format('Y-m-d'),
            'delivery_time'      => CartService::$time_delivery,
            'delivery_change_at' => $this->delivery_change_at?->format('Y-m-d'),
            'forwarder_phone'    => $this->delivery?->forwarder_phone,
            'payment'            => new PaymentResource($this->whenLoaded('payment')),
            'address'            => new UserAddressResource(isset($this->extra['address']) ? new UserAddress($this->extra['address']) : $this->address),
            'cart'               => new CartResource($this->whenLoaded('cart')),
            'checks'             => CheckResource::collection($this->whenLoaded('checks')),
        ];
    }
}
