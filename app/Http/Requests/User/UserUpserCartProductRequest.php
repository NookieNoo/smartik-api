<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserUpserCartProductRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'product_price' => ['required', 'uuid', 'exists:product_prices,uuid'],
			'count'         => ['required', 'numeric', 'gte:0']
		];
	}
}