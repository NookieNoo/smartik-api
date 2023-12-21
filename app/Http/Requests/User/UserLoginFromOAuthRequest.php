<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserLoginFromOAuthRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'provider_type' => ['required', 'in:google,apple,vk'],
			'provider_id'   => ['required'],
			'data'          => ['array']
		];
	}
}