<?php

namespace App\Exceptions\Integration\SDG;

use App\Exceptions\Integration\SDGException;

class ParseARVNoProductException extends SDGException
{
    public function __construct (string $message = 'Parse ARV, no product', public array $line = [])
    {
        parent::__construct(
            message: $message,
            data: $line
        );
    }
}
