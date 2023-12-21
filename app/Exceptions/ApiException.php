<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class ApiException extends \Exception
{
	public function __construct (
		public $type = 'logic',
		public $message = 'unknown',
		public $code = Response::HTTP_UNPROCESSABLE_ENTITY,
		public $data = null
	) {
		parent::__construct($message, $code);
	}
}