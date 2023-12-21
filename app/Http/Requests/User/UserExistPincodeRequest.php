<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserExistPincodeRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'phone' => ['required', 'phone:AUTO,mobile']
		];
	}
}