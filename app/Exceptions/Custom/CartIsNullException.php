<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class CartIsNullException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'cart null',
			code: 404
		);
	}
}