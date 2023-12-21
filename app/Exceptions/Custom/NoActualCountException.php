<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class NoActualCountException extends ApiException
{
	public function __construct (float $count) {
		parent::__construct(
			message: 'no actual count',
			code: 404,
			data: $count
		);
	}
}