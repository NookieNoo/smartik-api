<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class CantCancelFinishException extends ApiException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'payment in finish status'
        );
    }
}