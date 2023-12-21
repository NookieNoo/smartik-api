<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserSetPincodeRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'pincode' => ['required', 'string', 'size:4']
		];
	}
}