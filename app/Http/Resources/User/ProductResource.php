<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class ProductResource extends JsonResource
{
	public function toArray ($request) {
		return [
			...$this->only([
				'uuid',
				'name'
			]),
		];
	}
}
