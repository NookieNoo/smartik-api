<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserLoginFromSocketRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'channel_name' => ['required'],
			'socket_id'    => ['required']
		];
	}
}