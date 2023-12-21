<?php

namespace App\Listeners;

use App\Events\Admin\AdminHideActualEvent;
use App\Events\Admin\AdminShowActualEvent;
use App\Models\ProductActual;

class AdminSubscriber
{

    public function actualShow ($event)
    {
        if ($event->data instanceof ProductActual) {
            $event->data->update(['hidden' => false]);
        }
    }

    public function actualHide ($event)
    {
        if ($event->data instanceof ProductActual) {
            $event->data->update(['hidden' => true]);
        }
    }

    public function subscribe ($events): array
    {
        return [
            AdminShowActualEvent::class => 'actualShow',
            AdminHideActualEvent::class => 'actualHide',
        ];
    }
}