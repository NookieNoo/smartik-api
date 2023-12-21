<?php

namespace App\Services\KKM\Enums;

enum KKMProductType: int
{
    case FULL_PREPAYMENT        = 1;
    case PARTIAL_PREPAYMENT     = 2;
    case PREPAID_EXPENSE        = 3;
    case FULL_PAYMENT           = 4;
    case PARTIAL_PAYMENT_CREDIT = 5;
    case NO_PAYMENT_CREDIT      = 6;
    case PAYMENT_CREDIT         = 7;
}