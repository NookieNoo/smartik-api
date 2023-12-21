<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
	public function toArray ($request) {
		return array_merge(
			$this->only([
				'uuid',
				'address',
				'address_full',
				'name',
				'flat',
				'floor',
				'entrance',
				'default'
			]),
			[
				'address_location' => $this->address_location ? ['lat' => $this->address_location?->latitude, 'lng' => $this->address_location?->longitude] : null
			],
			$this->additional
		);
	}
}