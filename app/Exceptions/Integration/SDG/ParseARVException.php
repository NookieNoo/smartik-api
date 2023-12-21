<?php

namespace App\Exceptions\Integration\SDG;

use App\Exceptions\Integration\SDGException;

class ParseARVException extends SDGException
{
    public function __construct (public array $report)
    {
        parent::__construct(
            message: 'Ошибка парсинга ARV',
            data: $report
        );
    }
}