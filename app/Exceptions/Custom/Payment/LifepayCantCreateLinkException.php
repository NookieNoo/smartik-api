<?php

namespace App\Exceptions\Custom\Payment;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class LifepayCantCreateLinkException extends ApiException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'cant create link for payment',
        );
    }
}