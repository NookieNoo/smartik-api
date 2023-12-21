<?php

namespace App\Notifications;

use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\RoutesNotifications;

trait RoutesThrottledNotifications
{
    use RoutesNotifications {
        RoutesNotifications::notify as parentNotify;
    }

    public function notify ($instance)
    {
        if ($instance instanceof ThrottledNotification) {
            $key = $this->throttleKey($instance);
            if ($this->limiter()->tooManyAttempts($key, $this->maxAttempts())) {
                Log::notice("Skipping sending notification with key `$key`. Rate limit reached.");
                return;
            }

            $this->limiter()->hit($key, $instance->throttleDecaySeconds());
        }
        $this->parentNotify($instance);
    }

    protected function limiter ()
    {
        return app(RateLimiter::class);
    }

    /**
     * Build the notification throttle key from the Notification class name,
     * the Notification's throttle key id and the current user's id.
     *
     * Output example: productupdated|1|10
     */
    protected function throttleKey ($instance)
    {
        return Str::lower($instance->throttleKeyId() . '|' . $this->getAuthIdentifier());
    }

    protected function maxAttempts ()
    {
        return 1;
    }
}