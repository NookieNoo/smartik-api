<?php

namespace App\Exceptions\Integration;

use Illuminate\Http\Response;

class SDGException extends \Exception
{
    public function __construct (
        public $message = 'unknown',
        public $data = null
    )
    {
        parent::__construct($message);
    }
}