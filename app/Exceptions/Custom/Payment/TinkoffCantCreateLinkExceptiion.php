<?php

namespace App\Exceptions\Custom\Payment;

use App\Exceptions\ApiException;

class TinkoffCantCreateLinkExceptiion extends ApiException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'cant create link for payment',
        );
    }
}
