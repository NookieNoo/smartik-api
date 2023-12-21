<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserSignupRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'uuid' => ['required', 'uuid'],
		];
	}
}