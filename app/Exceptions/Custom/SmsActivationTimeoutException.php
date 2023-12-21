<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class SmsActivationTimeoutException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'time',
			code: Response::HTTP_UNAUTHORIZED
		);
	}
}