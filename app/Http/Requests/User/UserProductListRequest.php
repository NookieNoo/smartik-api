<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserProductListRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'catalog' => ['required', 'exists:catalogs,slug'],
			'after'   => ['nullable', 'uuid'],
			'order'   => ['nullable', 'in:price,expired'],
			'desc'    => ['nullable', 'boolean'],
			'filter'  => ['nullable', 'string']
		];
	}
}