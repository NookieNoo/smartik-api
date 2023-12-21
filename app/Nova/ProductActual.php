<?php

namespace App\Nova;

use App\Nova\Actions\ExportProductActualsFeed;
use Illuminate\Http\Request;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Trin4ik\NovaSwitcher\NovaSwitcher;

class ProductActual extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ProductActual::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    public static function label ()
    {
        return "Витрина";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = ['product.brand.name', 'product.name'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields (NovaRequest $request)
    {
        return [
            ID::make('id')->hide(),
            Text::make('ID продукта', 'product_id')->sortable()->onlyOnIndex(),
            BelongsTo::make('Поставщик', 'provider', Provider::class)->resolveUsing(function ($model) {
                return $model->resource->name;
            })->sortable(),

            BelongsTo::make('Продукт', 'product', Product::class)->searchable()->hideFromIndex(),
            BelongsTo::make('Продукт', 'product', Product::class)->displayUsing(function ($model) {
                if (mb_strlen($model->resource->name) <= 50) {
                    return $model->resource->name;
                }
                $part = strip_tags(mb_substr($model->resource->name, 0, 50));
                return $part . " ...";
            })->onlyOnIndex()->sortable()->withMeta(['sortableUriKey' => 'products.name']),

            Boolean::make('Сток?', 'from_stock')->help('Если стоит "сток", то товар помечается как собственный склад и не будет удалён после 15, останется в указанном количестве на стоке')->sortable(),
            Text::make('Остаток', 'count')->textAlign('right')->rules('required')->sortable(),
            Text::make('Цена', 'price')->textAlign('right')->rules('required')->sortable()->withMeta(['sortableUriKey' => 'product_prices.price']),
            Number::make('Цена для Смартика', 'product_price.finish_price')->step(0.01)->onlyOnForms()->required(),
            Number::make('РРЦ', 'product_price.start_price')->step(0.01)->onlyOnForms()->required(),
            Date::make('Дата производства', 'product_price.manufactured_at')
                ->sortable()
                ->withMeta(['sortableUriKey' => 'product_prices.manufactured_at'])
                ->displayUsing(fn ($_) => $this->product_price->manufactured_at->format('d.m.Y'))
                ->onlyOnIndex(),
            Date::make('Годность до', 'product_price.expired_at')
                ->sortable()
                ->withMeta(['sortableUriKey' => 'product_prices.expired_at'])
                ->displayUsing(fn ($_) => $this->product_price->expired_at->format('d.m.Y'))
                ->onlyOnIndex(),
            Date::make('Дата производства', 'product_price.manufactured_at')->onlyOnForms()->required(),
            Date::make('Срок годности', 'product_price.expired_at')->onlyOnForms()->required(),
            //Button::make('Скрыть', 'hide')->style('danger')->visible((bool)!$this->model()->hidden)->event(AdminHideActualEvent::class)->reload(),
            //Button::make('Отобразить', 'show')->style('success')->visible((bool)$this->model()->hidden)->event(AdminShowActualEvent::class)->reload(),
            NovaSwitcher::make('Доступность', 'hidden')->reverse()->withLabels(true: "Вкл.", false: "Выкл.")->sortable()
        ];
    }

    protected static function afterValidation (NovaRequest $request, $validator)
    {
        if ($request->input('product_price_finish_price') === null) {
            $validator->errors()->add('product_price.finish_price', 'Необходимый параметр');
        }
        if ($request->input('product_price_start_price') === null) {
            $validator->errors()->add('product_price.start_price', 'Необходимый параметр');
        }
        if ($request->input('product_price_manufactured_at') === null) {
            $validator->errors()->add('product_price.manufactured_at', 'Необходимый параметр');
        }
        if ($request->input('product_price_expired_at') === null) {
            $validator->errors()->add('product_price.expired_at', 'Необходимый параметр');
        }
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
        return [
            ExportAsCsv::make()->withFormat(function ($model) {
                return [
                    'ID продукта' => $model->product->id,
                    'Поставщик' => $model->provider->name,
                    'Продукт' => $model->product->name,
                    'Остаток' => $model->count,
                    'Цена' => $model->price,
                    'Дата производства' => $model->product_price->manufactured_at->format('Y-m-d H:i:s'),
                    'Годность до' => $model->product_price->expired_at->format('Y-m-d H:i:s'),
                    'Доступность' => !$model->hidden ? 'Да' : 'Нет',
                ];
            }),
            ExportProductActualsFeed::make(),
        ];
    }

    public function authorizedToReplicate (Request $request)
    {
        return false;
    }

    public static function indexQuery (NovaRequest $request, $query)
    {
        return $query
            ->withoutGlobalScopes()
            ->join('products', 'product_actuals.product_id', '=', 'products.id')
            ->select(['products.name'])
            ->join('product_prices', 'product_actuals.product_price_id', '=', 'product_prices.id')
            ->select(['product_prices.price', 'product_prices.expired_at', 'product_prices.manufactured_at'])
            ->select('product_actuals.*');
    }
}
