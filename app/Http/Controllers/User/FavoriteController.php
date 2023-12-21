<?php

namespace App\Http\Controllers\User;

use App\Exceptions\Custom\FavoriteWrongTypeException;
use App\Exceptions\Vendor\ModelNotFoundException;
use App\Http\Controllers\ApiController;
use App\Http\Resources\User\FavoriteResource;
use App\Http\Resources\User\ProductFullResource;
use App\Models\Favorite;
use App\Models\Product;
use App\Services\ActiveApi\Attributes\Title;

#[
	Title('Избранное', 'favorite'),
]
class FavoriteController extends ApiController
{
	#[
		Title('Список по типу', 'list'),
	]
	public function list (string $model) {
		$with = [];
		switch ($model) {
			case 'product':
			{
				$model = Product::class;
				$with = ['actuals', 'actuals.product_price'];
				break;
			}
			default:
			{
				$model = false;
			}
		}

		if (!$model) {
			throw new FavoriteWrongTypeException;
		}

		$favorites = Favorite::query()
			->where([
				'model_type' => $model,
				'user_type'  => $this->user::class,
				'user_id'    => $this->user->id
			])
			->with(['model' => $with])
			->get();

		return $this->send(FavoriteResource::collection($favorites));
	}

	#[
		Title('Добавить', 'add'),
	]
	public function add (string $model, string $uniq) {
		$with = [];
		switch ($model) {
			case 'product':
			{
				$with = ['actuals', 'actuals.product_price'];
				$model = Product::where('uuid', $uniq)->first();
				break;
			}
			default:
			{
				throw new ModelNotFoundException;
			}
		}

		Favorite::updateOrCreate([
			'user_type'  => $this->user::class,
			'user_id'    => $this->user->id,
			'model_type' => $model::class,
			'model_id'   => $model->id
		]);

		$favorites = Favorite::query()
			->where([
				'model_type' => $model::class,
				'user_type'  => $this->user::class,
				'user_id'    => $this->user->id
			])
			->with(['model' => $with])
			->get();

		return $this->send(FavoriteResource::collection($favorites));
	}

	#[
		Title('Удалить', 'remove'),
	]
	public function remove (string $model, string $uniq) {
		$with = [];
		switch ($model) {
			case 'product':
			{
				$with = ['actuals', 'actuals.product_price'];
				$model = Product::where('uuid', $uniq)->first();
				break;
			}
			default:
			{
				throw new ModelNotFoundException;
			}
		}

		Favorite::where([
			'user_type'  => $this->user::class,
			'user_id'    => $this->user->id,
			'model_type' => $model::class,
			'model_id'   => $model->id
		])->delete();

		$favorites = Favorite::query()
			->where([
				'model_type' => $model::class,
				'user_type'  => $this->user::class,
				'user_id'    => $this->user->id
			])
			->with(['model' => $with])
			->get();

		return $this->send(FavoriteResource::collection($favorites));
	}
}