<?php

namespace App\Jobs\Push;

use App\Enums\CartStatus;
use App\Events\System\SystemPushNotificationThrowCartEvent;
use App\Models\Cart;
use App\Models\EventLog;
use App\Models\User;
use App\Notifications\Push\ThrowCart120MinNotification;
use App\Notifications\Push\ThrowCart30MinNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ThrowedCartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $iterations = [
        [
            "minutes" => 30,
            "push"    => ThrowCart30MinNotification::class,
        ],
        [
            "minutes" => 120,
            "push"    => ThrowCart120MinNotification::class,
        ]
    ];

    public function __construct (?Carbon $now = null) {}

    public function handle ()
    {
        $carts = Cart::select(['carts.*', 'users.last_active_at'])
            ->leftJoin('users', 'users.id', 'carts.user_id')
            ->where('users.last_active_at', '<=', ($this->now ?? now())->subMinutes($this->iterations[0]['minutes']))
            ->where('carts.status', CartStatus::ACTIVE)
            ->get();

        $carts->each(function ($cart) {
            $iteration = EventLog::query()
                ->where('user_type', User::class)
                ->where('user_id', $cart->user_id)
                ->where('model_type', Cart::class)
                ->where('model_id', $cart->id)
                ->where('event', SystemPushNotificationThrowCartEvent::class)
                ->count();


            if (isset($this->iterations[$iteration]) && now()->diffInMinutes($cart->last_active_at) >= $this->iterations[$iteration]['minutes']) {
                $user = User::findOrFail($cart->user_id);
                event(new SystemPushNotificationThrowCartEvent($cart, $user, [
                    'push' => $this->iterations[$iteration],
                ]));
                $user->notify(new $this->iterations[$iteration]['push']);
            }
        });
    }
}