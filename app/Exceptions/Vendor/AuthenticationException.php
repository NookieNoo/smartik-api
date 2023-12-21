<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class AuthenticationException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'unauthenticated',
			code: Response::HTTP_FORBIDDEN
		);
	}
}