<?php

namespace App\Nova;

use App\Enums\PromocodeType;
use App\Nova\Filters\PromoPersonal;
use App\Nova\Filters\TagsFilter;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\Tags\Tag;
use Spatie\TagsField\Tags;
use Trin4ik\NovaSwitcher\NovaSwitcher;

class PromoTag extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\PromoTag::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Настройка тегов";
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
            ID::make()->sortable(),
            Text::make('Тэг', 'tag')->rules(['required'])->resolveUsing(function (?Tag $tag = null) {
                return $tag->name ?? "";
            }),
            Hidden::make('tag_id')->updateRules(['required']),
            Boolean::make('Только новые пользователи?', 'only_new_users'),
            Boolean::make('Отключить минимальную сумму?', 'disable_minimum_sum'),
            Boolean::make('Отключить платную доставку?', 'disable_delivery'),
            Number::make('Кол-во применений', 'max_uses')
                ->default(0)
                ->help('0 -- сколько угодно применений'),
            Number::make('Сбрасывать срок применений через', 'max_uses_per_days')
                ->hide()
                ->dependsOn(['max_uses'], function (Text $field, NovaRequest $request, FormData $formData) {
                    if ((int)$formData['max_uses'] > 0) {
                        $field->show();
                    }
                })
                ->default(0)
                ->help('указывается в количестве дней. для месяца -- 30.<br />0 -- не сбрасывать.'),
            NovaSwitcher::make('Активен', 'active')->default(true),
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

    protected static function afterCreationValidation (NovaRequest $request, $validator)
    {
        $tag = Tag::findFromString($request->input('tag'), 'promo');
        if ($tag && \App\Models\PromoTag::where('tag_id', $tag->id)->count()) {
            $validator->errors()->add('tag', 'Для этого тега уже есть правила');
        }
    }

    protected static function afterUpdateValidation (NovaRequest $request, $validator)
    {
        $tag = Tag::findFromString($request->input('tag'), 'promo');
        if ($tag && \App\Models\PromoTag::where('tag_id', $tag->id)->count() && $tag->id !== (int)$request->input('tag_id')) {
            $validator->errors()->add('tag', 'Для этого тега уже есть правила');
        }
    }

    protected static function fillFields (NovaRequest $request, $model, $fields): array
    {
        $models = parent::fillFields($request, $model, $fields);
        $models[0]->tag_id = Tag::findOrCreateFromString($request->input('tag'), 'promo')->id;
        unset($models[0]->tag);
        return $models;
    }
}
