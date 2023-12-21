<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class CartProductCountException extends ApiException
{
	public function __construct (array $errors) {
		parent::__construct(
			message: 'amount error',
			data: $errors
		);
	}
}