<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Exceptions\Custom\PromocodeException;
use App\Models\Promo;
use App\Models\PromoTag;
use App\Models\User;
use App\Models\UserPromo;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromoPolicy
{
    use HandlesAuthorization;

    public function use (User $user, Promo $promo): bool
    {
        if (!$promo->active) {
            throw new PromocodeException('active');
        }
        if ($promo->started_at && $promo->started_at->gt(Carbon::now())) {
            throw new PromocodeException('early', $promo->started_at->format('d.m.Y'));
        }
        if ($promo->ended_at && $promo->ended_at->lt(Carbon::now())) {
            throw new PromocodeException('late', $promo->ended_at->format('d.m.Y'));
        }
        if ($promo->count && $promo->uses() >= $promo->count) {
            throw new PromocodeException('count', $promo->count);
        }
        if (!$promo->reusable && $promo->uses($user) > 0) {
            throw new PromocodeException('reusable');
        }
        if ($promo->reusable && $promo->reusable_limit > 0 && $promo->uses($user) >= $promo->reusable_limit) {
            throw new PromocodeException('reusable_limit', $promo->reusable_limit);
        }

        // Не свой ли персональный промо ты вбиваешь
        if (substr($promo->code, 3) === $user->system_name) {
            throw new PromocodeException('your promo');
        }

        // Промо за приведённого друга можно применять только из своего аккаунта.
        $userPromo = UserPromo::where('promo_id', $promo->id)->first();
        if ($userPromo && ($userPromo->user_id !== $user->id || $userPromo->used_at !== null)) {
            throw new PromocodeException('no access', [
                $userPromo->user_id, $userPromo->used_at?->format('Y-m-d H:i:s')
            ]);
        }
        return true;
    }

    public function use_tags (User $user, Promo $promo): bool
    {
        $tags = $promo->tags;
        $promo_tags = PromoTag::whereIn('tag_id', $tags->pluck('id'));
        $promo_tags->each(function (PromoTag $tag) use ($user) {
            if ($tag->active) {

                if ($tag->only_new_users && $user->orders()->whereNotIn('status', OrderStatus::canceled())->count() >= $tag->max_uses) {
                    throw new PromocodeException('only_for_first_order', $tag->max_uses);
                }
                if ($tag->max_uses > 0 && $tag->uses($user) >= $tag->max_uses) {
                    throw new PromocodeException('tags max_uses', $tag->max_uses);
                }
            }
        });

        return true;
    }

    public function create ()
    {
        return true;
    }

    public function update ()
    {
        return true;
    }

    public function replicate ()
    {
        return true;
    }

    public function delete ()
    {
        return true;
    }

    public function restore ()
    {
        return true;
    }

    public function view ()
    {
        return true;
    }

    public function viewAny ()
    {
        return true;
    }
}
