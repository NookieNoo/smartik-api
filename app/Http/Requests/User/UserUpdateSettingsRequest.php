<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserUpdateSettingsRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'settings' => ['required', 'array'],
		];
	}
}