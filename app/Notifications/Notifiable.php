<?php

namespace App\Notifications;

use Illuminate\Notifications\HasDatabaseNotifications;

trait Notifiable
{
    use HasDatabaseNotifications, RoutesThrottledNotifications;
}