<?php

namespace App\Http\Resources\User;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;


class FavoriteResource extends JsonResource
{
	public function toArray ($request) {

		$data = null;
		switch ($this->model_type) {
			case Product::class:
			{
				$data = [
					'name'   => $this->model->name,
					'uuid'   => $this->model->uuid,
					'prices' => $this->model->actuals->count() ? $this->model->actuals->map(fn($actual) => [
						'uuid'             => $actual->product_price->uuid,
						'price'            => $actual->price,
						'discount_percent' => $actual->discount_percent,
					]) : null
				];
				break;
			}
		}

		return $data;
	}
}
