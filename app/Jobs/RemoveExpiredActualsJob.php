<?php

namespace App\Jobs;

use App\Enums\CartProductStatus;
use App\Enums\CartStatus;
use App\Events\System\SystemChangeStatusCartEvent;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Events\System\SystemRemoveProductActualEvent;
use App\Models\Cart;
use App\Models\ProductActual;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveExpiredActualsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct (protected int $daysToExpire = 1) {}

    public function handle ()
    {
        $products = ProductActual::where('from_stock', true)->whereRelation('product_price', 'expired_at', '<=', now()->addDays($this->daysToExpire)->endOfDay())->get();
        $products->each(function ($item) {
            event(new SystemRemoveProductActualEvent($item, extra: ['why' => 'expired']));
            $item->delete();
        });
    }
}
