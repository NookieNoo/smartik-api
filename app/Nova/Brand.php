<?php

namespace App\Nova;

use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Novius\LaravelNovaOrderNestedsetField\OrderNestedsetField;
use Outl1ne\NovaSortable\Traits\HasSortableRows;
use Outl1ne\NovaColorField\Color;

class Brand extends Resource
{
    use HasSortableRows;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Brand::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Бренд";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name',
    ];

    public static $tableStyle = 'tight';

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields (NovaRequest $request)
    {
        return [
            ID::make(),

            Images::make('Логотип', 'logo')
                ->conversionOnIndexView('thumb')
                ->conversionOnDetailView('big'),

            Text::make('Название', 'name'),
            Slug::make('uri', 'slug')->from('name'),
            Text::make('Ссылка в приложение', function () {
                $link = 'smartik://brand/' . $this->slug;
                return '<a class="link-default" href="' . $link . '"> ' . $link . '</a>';
            })->asHtml()->hideFromIndex(),

            Color::make('Цвет текста', 'color'),
            Color::make('Фон', 'background'),

            HasMany::make('Продукты', 'products', Product::class)
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
}
