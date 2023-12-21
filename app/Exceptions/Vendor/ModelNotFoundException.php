<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class ModelNotFoundException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'item not found',
			code: Response::HTTP_NOT_FOUND
		);
	}
}