<?php

namespace App\Exceptions\Custom;

use App\Enums\OrderStatus;
use App\Exceptions\ApiException;

class CantChangeOrderStatusException extends ApiException
{
    public function __construct (OrderStatus $status)
    {
        parent::__construct(
            message: 'cant change status from ' . $status->value
        );
    }
}