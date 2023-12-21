<?php

namespace App\Exceptions\Vendor;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class ValidationException extends ApiException
{
	public function __construct (\Throwable $exception) {
		parent::__construct(
			type: "validator",
			message: "validation error",
			data: (new \Illuminate\Validation\ValidationException($exception->validator))->errors(),
		);
	}
}