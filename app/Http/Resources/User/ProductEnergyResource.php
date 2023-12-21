<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class ProductEnergyResource extends JsonResource
{
	public function toArray ($request) {
		return $this->only([
			'calories',
			'protein',
			'fat',
			'carbon',
		]);
	}
}
