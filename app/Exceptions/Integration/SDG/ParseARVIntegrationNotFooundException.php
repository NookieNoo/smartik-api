<?php

namespace App\Exceptions\Integration\SDG;

use App\Exceptions\Integration\SDGException;

class ParseARVIntegrationNotFooundException extends SDGException
{
    public function __construct (string $message = 'Parse ARV, no integration', public array $head = [])
    {
        parent::__construct(
            message: $message,
            data: $head
        );
    }
}
