<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class AccessDeniedException extends ApiException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'access denied',
            code: Response::HTTP_FORBIDDEN
        );
    }
}