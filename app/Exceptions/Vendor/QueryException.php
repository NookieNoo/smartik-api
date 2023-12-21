<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class QueryException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'mysql error',
			code: Response::HTTP_INTERNAL_SERVER_ERROR
		);
	}
}