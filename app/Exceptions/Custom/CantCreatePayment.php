<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class CantCreatePayment extends ApiException
{
    public function __construct (string $message)
    {
        parent::__construct(
            message: 'cant create payment',
            data: $message
        );
    }
}