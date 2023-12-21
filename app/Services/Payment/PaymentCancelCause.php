<?php

namespace App\Services\Payment;

enum PaymentCancelCause: string
{
    case USER    = 'user';
    case MANAGER = 'manager';
    case DRIVER  = 'driver';
}