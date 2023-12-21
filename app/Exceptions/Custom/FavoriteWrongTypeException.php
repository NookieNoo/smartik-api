<?php

namespace App\Exceptions\Custom;

use App\Exceptions\ApiException;
use Illuminate\Http\Response;

class FavoriteWrongTypeException extends ApiException
{
	public function __construct () {
		parent::__construct(
			message: 'favorite type not found'
		);
	}
}