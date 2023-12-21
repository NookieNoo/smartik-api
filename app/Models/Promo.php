<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PromocodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\HasTags;
use stdClass;

class Promo extends Model
{
    use HasTags;

    protected $casts = [
        'reusable'   => 'boolean',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'active'     => 'boolean',
        'type'       => PromocodeType::class
    ];

    public function personal (): HasOne
    {
        return $this->hasOne(UserPromo::class);
    }

    public function uses (?User $user = null): int
    {
        $count = DB::table('cart_promo')
            ->leftJoin('carts', 'carts.id', 'cart_promo.cart_id')
            ->leftJoin('orders', 'orders.id', 'carts.order_id')
            ->whereNotIn('orders.status', OrderStatus::canceled())
            ->where('cart_promo.promo_id', $this->id);
        if ($user) {
            $count->where('carts.user_id', $user->id);
        }
        return $count->count();
    }

    /*
     * Костыльный метод.
     * Странно, что в тегах и ограничение на применение промо и баффы.
     */
    public function getTagsBuff (): stdClass
    {
        $result = [
            'disable_minimum_sum' => false,
            'disable_delivery'    => false
        ];
        $tags = PromoTag::whereIn('tag_id', $this->tags()->pluck('id'))->get();
        $tags->each(function (PromoTag $tag) use (&$result) {
            if (!$tag->active) return;
            if ($tag->disable_minimum_sum) $result['disable_minimum_sum'] = true;
            if ($tag->disable_delivery) $result['disable_delivery'] = true;
        });
        return (object)$result;
    }
}