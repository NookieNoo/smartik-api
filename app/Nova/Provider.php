<?php

namespace App\Nova;

use App\Enums\ProductPriceSource;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Novius\LaravelNovaOrderNestedsetField\OrderNestedsetField;

class Provider extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Provider::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Поставщики";
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
            Slug::make('Slug', 'slug')
                ->from('name')
                ->rules('required')
                ->creationRules('unique:providers,slug')
                ->updateRules('unique:providers,slug,{{resourceId}}'),
            Number::make('Маржа', 'margin')->min(0)->help('Указывается в процентах, где 100% -- двойная наценка, а 0% -- без наценки')->onlyOnForms(),
            Text::make('Маржа', function () {
                return $this->margin . '%';
            }),

            Text::make('ИНН', 'inn'),
            Boolean::make('Забор силами СДГ', 'shipperpoint_id')->falseValue(null),
            Text::make('Код склада в УЛО', 'shipperpoint_id')
                ->hide()
                ->dependsOn('shipperpoint_id', function (Text $field, NovaRequest $request, FormData $formData) {
                    if ($formData->shipperpoint_id) {
                        $field->show()->rules('required');
                    }
                })
                ->hideFromIndex(),
            Select::make('Тип', 'type')->options(ProductPriceSource::titles())->displayUsingLabels()->default(ProductPriceSource::MANUFACTURER->value),
        ];
    }

    protected static function fillFields (NovaRequest $request, $model, $fields): array
    {
        if ($request->input('shipperpoint_id') === "0") {
            $request->merge(['shipperpoint_id' => null]);
        }
        return parent::fillFields($request, $model, $fields);
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
