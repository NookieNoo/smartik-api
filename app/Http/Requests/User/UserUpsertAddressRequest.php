<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserUpsertAddressRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'uuid'                 => ['required', 'uuid'],
			'name'                 => ['nullable', 'string'],
			'default'              => ['nullable', 'boolean'],
			'address'              => ['nullable', 'string'],
			'address_full'         => ['nullable', 'string'],
			'address_location'     => ['nullable'],
			'address_location.lat' => ['required_with:address_location.lng', 'numeric'],
			'address_location.lng' => ['required_with:address_location.lat', 'numeric'],
			'flat'                 => ['nullable', 'string'],
			'entrance'             => ['nullable', 'string'],
			'floor'                => ['nullable', 'string']
		];
	}
}