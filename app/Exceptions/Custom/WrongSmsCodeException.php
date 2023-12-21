<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class WrongSmsCodeException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'wrong sms code'
		);
	}
}