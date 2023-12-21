<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class UserUuidExistExtension extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'uuid exist',
			code: Response::HTTP_CONFLICT
		);
	}
}