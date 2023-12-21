<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserChangeCountCartRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'count' => ['required', 'numeric', 'gte:0'],
		];
	}
}