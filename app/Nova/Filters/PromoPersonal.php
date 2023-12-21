<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PromoPersonal extends BooleanFilter
{

    public $name = 'Тип';

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
        if ($value['personal']) {
            $query->whereDoesntHave('personal');
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
            'Скрыть персональные' => 'personal'
        ];
    }

    public function default ()
    {
        return ['personal' => true];
    }
}
