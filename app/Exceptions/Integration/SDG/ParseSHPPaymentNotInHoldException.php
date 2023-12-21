<?php

namespace App\Exceptions\Integration\SDG;

use App\Exceptions\Integration\SDGException;

class ParseSHPPaymentNotInHoldException extends SDGException
{
    public function __construct (array $report)
    {
        parent::__construct(
            message: 'Оплата заказ из SHP не в HOLD',
            data: $report
        );
    }
}