<?php

namespace App\Nova;

use App\Enums\PromocodeType;
use App\Nova\Filters\PromoPersonal;
use App\Nova\Filters\TagsFilter;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\TagsField\Tags;
use Trin4ik\NovaSwitcher\NovaSwitcher;

class Promo extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Promo::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Промокоды";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'code'
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
            ID::make()->sortable(),
            Text::make('Название', 'name')->rules(['required']),
            Text::make('Код', 'code')->rules(['required']),
            Select::make('Тип', 'type')->options(PromocodeType::titles())->displayUsingLabels()->default('delivery'),
            Number::make('Скидка', 'discount')->step(1)->dependsOn('type', function ($field, NovaRequest $request, $formData) {
                if ($formData->type === PromocodeType::DELIVERY->value) {
                    $field->hide();
                } else {
                    $field->show()->rules(['required']);
                }
            }),
            Number::make('Количество', 'count')->help('0 -- бесконечные'),
            Boolean::make('Многоразовые', 'reusable'),
            Number::make('Лимит повторов', 'reusable_limit')
                ->hide()
                ->dependsOn(['reusable'], function (Text $field, NovaRequest $request, FormData $formData) {
                    if ($formData['reusable']) {
                        $field->show();
                    }
                })
                ->help('0 -- бесконечные'),
            Number::make('Работает от суммы', 'from_sum'),
            NovaSwitcher::make('Активен', 'active'),
            Tags::make('Теги')->withMeta(['placeholder' => 'Добавить теги...'])->type('promo'),
            DateTime::make('Дата старта', 'started_at')->hideFromIndex(),
            DateTime::make('Дата конца', 'ended_at')->hideFromIndex(),
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
        return [
            new PromoPersonal,
            new TagsFilter('promo')
        ];
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
                    'id'                => $model->id,
                    'Наименование'      => $model->name,
                    'Код'               => $model->code,
                    'Тип'               => $model->type->title(),
                    'Скидка'            => $model->discount,
                    'Количество'        => $model->count,
                    'Многоразовые'      => $model->reusable,
                    'Лимит повторов'    => $model->reusable_limit,
                    'Работает от суммы' => $model->from_sum,
                    'Активен'           => $model->active,
                    'Теги'              => implode(', ', $model->tags->pluck('name')->toArray()),
                    'Начало действия'   => $model->started_at?->format('Y-m-d H:i:s') ?? 'null',
                    'Конец действия'    => $model->ended_at?->format('Y-m-d H:i:s') ?? 'null',
                ];
            }),
        ];
    }
}
