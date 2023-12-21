<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserCheckPincodeRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'phone'   => ['required', 'phone:AUTO,mobile'],
			'pincode' => ['required', 'string']
		];
	}
}