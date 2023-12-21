<?php

namespace App\Services\KKM\Enums;

enum KKMPaymentType: string
{
    case PAYMENT                = 'payment';
    case REFUND                 = 'refund';
    case BUY                    = 'buy';
    case BUY_REFUND             = 'buy_refund';
    case SELL_CORRECTION        = 'sell_correction';
    case SELL_RETURN_CORRECTION = 'sell_return_correction';
    case BUY_CORRECTION         = 'buy_correction';
    case BUY_RETURN_CORRECTION  = 'buy_return_correction';
}