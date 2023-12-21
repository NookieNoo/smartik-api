<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Traits\WithUuid;
use Illuminate\Support\Str;

class OrderObserver
{
    use WithUuid;

    public function creating (Order $order)
    {
        if (!$order->uuid) {
            $order->uuid = (string)Str::uuid();
        }

        $user = User::find($order->user_id);
        $order->name = $user->system_name . '-' . str_pad((string)($user->orders()->withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}