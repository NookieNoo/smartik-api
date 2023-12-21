<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\Tags\Tag;

class TagsFilter extends BooleanFilter
{
    public $name = 'Теги';

    public function __construct (public ?string $type = null) {}

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
        $filter = array_keys(array_filter($value));
        if (!count($filter)) return $query;

        return $query->withAnyTags($filter, $this->type);
    }

    /**
     * Get the filter's available options.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function options (NovaRequest $request)
    {
        return Tag::whereType($this->type)->get()->pluck('name');
    }
}
