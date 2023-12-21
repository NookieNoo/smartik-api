<?php

namespace App\Exceptions\Integration;

use Illuminate\Http\Response;

class ImapException extends \Exception
{
    public function __construct (
        public $type = 'logic',
        public $message = 'unknown',
        public $data = null
    )
    {
        parent::__construct($message);
    }
}