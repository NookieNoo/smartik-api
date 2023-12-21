<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class PromoTag extends Model
{
    use SoftDeletes;

    protected $casts = [
        'only_new_users'      => 'boolean',
        'disable_minimum_sum' => 'boolean',
        'disable_delivery'    => 'boolean',
    ];

    public function tag (): HasOne
    {
        return $this->hasOne(Tag::class, 'id', 'tag_id');
    }

    public function uses (User $user = null): int
    {
        $count = DB::table('cart_promo')
            ->leftJoin('taggables', 'taggables.taggable_id', 'cart_promo.promo_id')
            ->leftJoin('carts', 'carts.id', 'cart_promo.cart_id')
            ->leftJoin('orders', 'orders.id', 'carts.order_id')
            ->whereNotIn('orders.status', OrderStatus::canceled())
            ->where('taggables.taggable_type', Promo::class)
            ->where('taggables.tag_id', $this->tag_id)
            ->where('carts.user_id', $user->id);

        if ($this->max_uses_per_days) {
            $count->where('orders.created_at', '>=', now()->subDays($this->max_uses_per_days));
        }

        return $count->count();
    }
}
