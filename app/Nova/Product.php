<?php

namespace App\Nova;

use Laravel\Nova\Actions\ExportAsCsv;
use App\Enums\ProductWeightType;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Product extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Product::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Продукты";
    }

    public static function searchableColumns ()
    {
        return ['id', 'name', 'eans.ean', 'brand.name'];
    }

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

            Images::make('Изображение', 'images')
                ->conversionOnIndexView('thumb')
                ->conversionOnDetailView('big'),

            BelongsTo::make('Бренд', 'brand', Brand::class),
            Text::make('Название', 'name'),
            Textarea::make('Описание', 'description')->hideFromIndex(),
            Textarea::make('Состав', 'compound')->hideFromIndex(),
            Number::make('Масса', 'weight')->min(0)->max(999)->step(0.001)->default(0),
            Select::make('Тип массы', 'weight_type')->options(ProductWeightType::titles())->displayUsingLabels()->default('g'),
            Number::make('РРЦ', "price")->default(0),
            Select::make('НДС', "vat")->options([10 => "10%", 20 => "20%"])->default(10),
            Boolean::make('Маркируемый товар', 'marked')->hideFromIndex(),
            Number::make('СГ в днях', "expire_days")->hideFromIndex()->default(0),
            Text::make('Ссылка в приложение', function () {
                $link = 'smartik://product/' . $this->uuid;
                return '<a class="link-default" href="' . $link . '"> ' . $link . '</a>';
            })->asHtml()->hideFromIndex(),
            HasOne::make('Энергитическая ценность', "energy", ProductEnergy::class),
            BelongsToMany::make('Каталоги', "catalogs", Catalog::class),
            HasMany::make('ШтрихКоды', "eans", ProductEan::class),
            HasMany::make('Цены', "prices", ProductPrice::class),
            Boolean::make('Заморозка?', 'is_frozen')->hideFromIndex(),
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
        return [
            ExportAsCsv::make()->withFormat(function ($model) {
                return [
                    'id'           => $model->id,
                    'uuid'         => $model->uuid,
                    'EAN'          => $model->eans()?->first()?->ean,
                    'Наименование' => $model->name,
                    'Бренд'        => $model?->brand?->name,
                    'Изображение'  => $model->getFirstMedia('images')?->getUrl(),
                    'Масса'        => $model->weight,
                    'Тип массы'    => $model->weight_type?->title(),
                    'РРЦ'          => $model->price,
                    'Описание'     => $model->description,
                    'Состав'       => $model->compound,
                ];
            }),
        ];
    }


    public function authorizedToReplicate (Request $request): bool
    {
        return false;
    }

    public static function usesScout ()
    {
        return false;
    }
}
