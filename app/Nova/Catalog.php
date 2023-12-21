<?php

namespace App\Nova;

use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Novius\LaravelNovaOrderNestedsetField\OrderNestedsetField;
use Trin4ik\NovaSwitcher\NovaSwitcher;

class Catalog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Catalog::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */

    public function title ()
    {
        return str_repeat('--', $this->depth) . ' ' . $this->name;
    }

    public static function label ()
    {
        return "Каталог";
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

            Images::make('Иконка', 'icon')->conversionOnIndexView('thumb')->conversionOnDetailView('big'),

            OrderNestedsetField::make('Порядок')->showOnIndex(function () use ($request) {
                return ($request->viaResource === null);
            }),

            Text::make('Название', 'name')
                ->displayUsing(function ($name, $resource) {
                    return str_repeat('--', $resource->depth) . ' ' . $name;
                })->onlyOnIndex(),
            Text::make('Название', 'name')->hideFromIndex(),
            Slug::make('uri', 'slug')->from('name'),
            Text::make('Ссылка в приложение', function () {
                $link = 'smartik://catalog/' . $this->uuid;
                return '<a class="link-default" href="' . $link . '"> ' . $link . '</a>';
            })->asHtml()->hideFromIndex(),
            Select::make('Родитель', 'parent_id')
                ->options(function () {
                    return \App\Models\Catalog
                        ::where('id', '!=', $this->id)
                        ->get()
                        ->reduce(function ($options, $model) {
                            $options[$model['id']] = str_repeat('-- ', $model->depth) . $model['name'];
                            return $options;
                        }, []);
                })
                ->nullable()
                ->onlyOnForms(),

            NovaSwitcher::make('Доступность', 'hidden')->reverse()->withLabels(true: "Вкл.", false: "Выкл."),

            BelongsToMany::make('Продукты', "products", Product::class),
        ];
    }

    public static function indexQuery (NovaRequest $request, $query)
    {
        return $query->withDepth()->defaultOrder();
    }

    protected static function applyOrderings ($query, array $orderings)
    {
        return $query->defaultOrder();
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
