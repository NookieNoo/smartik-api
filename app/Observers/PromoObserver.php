<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use App\Traits\WithUuid;
use Illuminate\Support\Str;

class PromoObserver
{
    public function creating (Promo $promo)
    {
        if (!$promo->from_sum) {
            $promo->from_sum = 0;
        }
        if (!$promo->count) {
            $promo->count = 0;
        }
    }
}