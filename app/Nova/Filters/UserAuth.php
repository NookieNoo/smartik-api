<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class UserAuth extends BooleanFilter
{

    public $name = 'Пользователь';

    /**
     * Apply the filter to the given query.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply (NovaRequest $request, $query, $value)
    {
        if ($value['phone']) {
            $query->whereHas('phone');
        }
        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function options (NovaRequest $request)
    {
        return [
            'Только зареганные' => 'phone'
        ];
    }

    public function default ()
    {
        //return ['phone' => true];
    }
}
