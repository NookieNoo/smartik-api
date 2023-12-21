<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case START          = 'start';
    case HOLD           = 'hold';
    case DONE           = 'done';
    case ERROR          = 'error';
    case REFUND         = 'refund';
    case CANCELED_USER  = 'canceled:user';
    case CANCELED_ADMIN = 'canceled:admin';
    case CANCELED_TIME  = 'canceled:time';

    public static function canceled (): array
    {
        return [
            self::CANCELED_USER,
            self::CANCELED_ADMIN,
            self::CANCELED_TIME
        ];
    }

    public static function values (): array
    {
        return array_column(self::cases(), 'value');
    }
}