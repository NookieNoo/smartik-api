<?php

namespace App\Notifications;

interface ThrottledNotification
{
    public function throttleKeyId (): string;

    public function throttleDecaySeconds (): int;
}