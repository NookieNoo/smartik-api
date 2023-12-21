<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class UserPushToken extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\UserPushToken::class;

    public static $with = [];

    public static $perPageViaRelationship = 100;

    public static $clickAction = 'view';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    public static function label ()
    {
        return "Push токены";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    //public static $search = ['product.brand.name', 'product.name'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields (NovaRequest $request)
    {
        return [
            Text::make('Токен', 'token')->copyable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function cards (NovaRequest $request)
    {
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
    public function filters (NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function lenses (NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function actions (NovaRequest $request)
    {
        return [];
    }

    public function authorizedToReplicate (Request $request)
    {
        return false;
    }

    public function authorizedToDelete (Request $request)
    {
        return false;
    }

    public function authorizedToUpdate (Request $request)
    {
        return false;
    }

    public function authorizedTo (Request $request, $ability)
    {
        return false;
    }


    public function authorizedToView (Request $request)
    {
        return false;
    }
}
