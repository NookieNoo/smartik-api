<?php

namespace App\Nova;

use Carbon\Carbon;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class ProductPrice extends Resource
{
	/**
	 * The model the resource corresponds to.
	 *
	 * @var string
	 */
	public static $model = \App\Models\ProductPrice::class;

	public static $with = [];

	/**
	 * The single value that should be used to represent the resource when being displayed.
	 *
	 * @var string
	 */
	public static $title = 'id';

	public static function label () {
		return "Цены";
	}

	/**
	 * The columns that should be searched.
	 *
	 * @var array
	 */
	public static $search = [];

	public static $tableStyle = 'tight';

	/**
	 * Get the fields displayed by the resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function fields (NovaRequest $request) {
		return [
			Date::make('Дата', 'date')->withMeta(['pattern' => "[0-9]{4}-[0-9]{2}-[0-9]{2}"]),
			BelongsTo::make('Продукт', 'product', Product::class),
			BelongsTo::make('Поставщик', 'provider', Provider::class),
			Text::make('Количество', 'count'),
			Text::make('Цена', 'price'),
			Text::make('Цена розницы', 'start_price'),
			Text::make('Цена для нас', 'finish_price'),
			DateTime::make('Дата производства', 'manufactured_at'),
			DateTime::make('Годность до', 'expired_at'),
		];
	}

	/**
	 * Get the cards available for the request.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function cards (NovaRequest $request) {
		return [
			//ActiveUsers::make(),
		];
	}

	/**
	 * Get the filters available for the resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function filters (NovaRequest $request) {
		return [];
	}

	/**
	 * Get the lenses available for the resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function lenses (NovaRequest $request) {
		return [];
	}

	/**
	 * Get the actions available for the resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function actions (NovaRequest $request) {
		return [];
	}
}
