<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class PromocodeException extends ApiException
{
	public function __construct (string $type, mixed $data = null) {
		parent::__construct(
			message: 'promocode error',
			data: [
				'type'       => $type,
				'additional' => $data
			]
		);
	}
}