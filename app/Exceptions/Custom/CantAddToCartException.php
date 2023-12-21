<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class CantAddToCartException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'not enough quantity'
		);
	}
}