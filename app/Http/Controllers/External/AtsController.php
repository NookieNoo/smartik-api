<?php

namespace App\Http\Controllers\External;

use App\Attributes\ExternalSalt;
use App\Events\System\SystemAtsCancelOrderEvent;
use App\Events\System\SystemAtsDoneOrderEvent;
use App\Events\System\SystemAtsInRadiusOrderEvent;
use App\Events\System\SystemAtsOnWayOrderEvent;
use App\Events\System\SystemAtsPerformedOrderEvent;
use App\Http\Controllers\ApiController;
use App\Http\Requests\External\AtsCancelRequest;
use App\Http\Requests\External\AtsDoneRequest;
use App\Http\Requests\External\AtsOnWayRequest;
use App\Http\Requests\External\AtsPerformedRequest;
use App\Http\Requests\External\AtsRadiusRequest;
use App\Models\Order;
use App\Notifications\Push\OrderCancelNotification;
use App\Services\ActiveApi\Attributes\Title;

#[
    Title('Заказы', 'orders'),
    ExternalSalt("wj3h8034jh803jh0904kc58gyvh0m58up4uhmotnkmbr")
]
class AtsController extends ApiController
{
    public function performed (AtsPerformedRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        event(new SystemAtsPerformedOrderEvent($order, extra: $request->all()));
        return $this->send(true);
    }

    public function on_way (AtsOnWayRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        event(new SystemAtsOnWayOrderEvent($order, extra: $request->all()));
        return $this->send(true);
    }

    public function in_radius (AtsRadiusRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        event(new SystemAtsInRadiusOrderEvent($order, extra: $request->all()));
        return $this->send(true);
    }

    public function done (AtsDoneRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        event(new SystemAtsDoneOrderEvent($order, extra: $request->all()));
        return $this->send(true);
    }

    public function cancel (AtsCancelRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        event(new SystemAtsCancelOrderEvent($order, extra: $request->all()));
        return $this->send(true);
    }

    public function test (AtsCancelRequest $request, string $order)
    {
        $order = Order::where('name', $order)->firstOrFail();
        $order->user?->notify(new OrderCancelNotification($order));
        return $this->send(true);
    }
}