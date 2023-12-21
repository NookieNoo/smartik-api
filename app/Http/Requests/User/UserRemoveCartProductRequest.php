<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserRemoveCartProductRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'product_price' => ['required', 'uuid', 'exists:product_prices,uuid'],
		];
	}
}