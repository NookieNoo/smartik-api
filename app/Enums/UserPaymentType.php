<?php

namespace App\Enums;

enum UserPaymentType: string
{
    case CREDIT_CARD = 'creditcard';
    case SBP         = 'sbp';

    public static function values (): array
    {
        return array_column(self::cases(), 'value');
    }
}