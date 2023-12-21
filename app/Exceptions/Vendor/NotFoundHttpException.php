<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class NotFoundHttpException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: '404',
			code: Response::HTTP_NOT_FOUND
		);
	}
}