<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserPromocodeCartRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'code' => ['required', 'string'],
		];
	}
}