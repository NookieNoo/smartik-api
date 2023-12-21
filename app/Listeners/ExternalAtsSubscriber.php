<?php

namespace App\Listeners;


use App\Enums\OrderStatus;
use App\Events\System\SystemAtsCancelOrderEvent;
use App\Events\System\SystemAtsDoneOrderEvent;
use App\Events\System\SystemAtsInRadiusOrderEvent;
use App\Events\System\SystemAtsOnWayOrderEvent;
use App\Events\System\SystemAtsPerformedOrderEvent;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Facades\Gosnumber;
use App\Models\KkmCheck;
use App\Models\OrderDelivery;
use App\Notifications\Push\OrderCancelNotification;
use App\Notifications\Push\OrderDeliveryInRadiusNotification;
use App\Notifications\Push\OrderDeliveryOnWayNotification;
use App\Notifications\Push\OrderDeliveryPerformedNotification;

class ExternalAtsSubscriber
{

    public function status ($event, $type)
    {
        $order = $event->data;
        $gosnumber = Gosnumber::parse($event->extra['gosnumber'] ?? "")->get();
        if (!$order->delivery || $order->delivery->gosnumber !== $gosnumber) {
            OrderDelivery::create([
                'order_id'  => $order->id,
                'vehicle'   => $event->extra['vehicle'] ?? "",
                'gosnumber' => $gosnumber,
            ]);
            $order->refresh();
        }
        $order->delivery->update([
            $type . '_at' => $event->extra['at'] ?? now()
        ]);
    }

    public function performed ($event)
    {
        $this->status($event, "started");
        $event->data->update(['status' => OrderStatus::DELIVERY_PERFORMED]);
        event(new UserChangeStatusOrderEvent($event->data, extra: ['status' => OrderStatus::DELIVERY_PERFORMED]));
    }

    public function on_way ($event)
    {
        $this->status($event, "on_way");
        $event->data->update(['status' => OrderStatus::DELIVERY_ON_WAY]);
        event(new UserChangeStatusOrderEvent($event->data, extra: ['status' => OrderStatus::DELIVERY_ON_WAY]));
    }

    public function in_radius ($event)
    {
        $this->status($event, "in_radius");
        $event->data->user?->notify(new OrderDeliveryInRadiusNotification($event->data));
    }

    public function done ($event)
    {
        $this->status($event, "arrival");
        $event->data->update(['status' => OrderStatus::DONE]);
        event(new UserChangeStatusOrderEvent($event->data, extra: ['status' => OrderStatus::DONE]));
    }

    public function cancel ($event)
    {
        //$this->status($event, "cancel");
        if (!$event->data->status === OrderStatus::CANCELED_USER) {
            $event->data->update(['status' => OrderStatus::CANCELED_DRIVER]);
            event(new UserChangeStatusOrderEvent($event->data, extra: ['status' => OrderStatus::CANCELED_DRIVER]));
        }
    }

    public function subscribe ($events): array
    {
        return [
            SystemAtsPerformedOrderEvent::class => 'performed',
            SystemAtsOnWayOrderEvent::class     => 'on_way',
            SystemAtsInRadiusOrderEvent::class  => 'in_radius',
            SystemAtsDoneOrderEvent::class      => 'done',
            SystemAtsCancelOrderEvent::class    => 'cancel',
        ];
    }
}
