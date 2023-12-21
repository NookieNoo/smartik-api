<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class PhoneNotFoundException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'phone not found'
		);
	}
}