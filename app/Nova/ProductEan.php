<?php

namespace App\Nova;

use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class ProductEan extends Resource
{
	/**
	 * The model the resource corresponds to.
	 *
	 * @var string
	 */
	public static $model = \App\Models\ProductEan::class;

	public static $with = [];

	/**
	 * The single value that should be used to represent the resource when being displayed.
	 *
	 * @var string
	 */
	public static $title = 'ean';

	public static function label () {
		return "EAN";
	}

	/**
	 * The columns that should be searched.
	 *
	 * @var array
	 */
	public static $search = [
		'ean'
	];

	public static $tableStyle = 'tight';

	/**
	 * Get the fields displayed by the resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function fields (NovaRequest $request) {
		return [
			Text::make('ean'),
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
