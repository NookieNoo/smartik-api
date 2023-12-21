<?php

namespace App\Nova;

use App\Services\Showcase\CartService;
use Illuminate\Http\Request;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Cart extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Cart::class;

    public static $with = ['user', 'order'];

    public static $clickAction = 'view';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    public static function label ()
    {
        return "Корзины";
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

    /*
     * По поводу canceled корзин, я поленился и сделал `?:`, по-хорошему надо проверять на canceled:time
     */
    public function fields (NovaRequest $request)
    {
        return [
            ID::make('ID', 'id')->sortable(),
            BelongsTo::make('Пользователь', 'user', User::class)->sortable(),
            Text::make('Статус', function () {
                return '<span class="toasted ' . $this->status->style() . '">' . $this->status->title() . '</span>';
            })->asHtml(),
            BelongsTo::make('Заказ', 'order', Order::class)->sortable(),
            Text::make('Стоимость продукты', function () {
                return number_format($this->resource->cast()->sumProducts ?: $this->resource->cast()->sumProductsAll, 2, '.', ' ') . ' &#8381;';
            })->asHtml(),
            Text::make('Стоимость доставки', function () {
                return number_format($this->resource->cast()->deliveryPriceFinal, 2, '.', ' ') . ' &#8381;';
            })->asHtml(),
            Text::make('Стоимость итого', function () {
                return number_format($this->resource->cast()->sumFinal ?: $this->resource->cast()->sumCanceled, 2, '.', ' ') . ' &#8381;';
            })->asHtml(),
            Text::make('Ссылка в приложение', function () {
                $link = 'smartik://cart';
                return '<a class="link-default" href="' . $link . '"> ' . $link . '</a>';
            })->asHtml()->hideFromIndex(),
            Text::make('Дата создания', 'created_at')->resolveUsing(function () {
                return $this->created_at->format('d.m.Y H:i:s');
            })->asHtml()->textAlign('right')->sortable(),
            Text::make('af_status', 'user.install_type')->hideWhenCreating()->hideWhenUpdating(),
            Text::make('media_source', 'user.media_source')->onlyOnDetail(),
            Text::make('Campaign', 'user.campaign')->onlyOnDetail(),
            Text::make('Agency', 'user.agency')->onlyOnDetail(),
            HasMany::make('Состав', 'products', CartProduct::class),
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
                ini_set('max_execution_time', '90');

                $cast = $model->cast();
                return [
                    'id'                  => $model->id,
                    'Пользователь, id'    => $model->user?->id ?? 0,
                    'Пользователь, имя'   => $model->user?->name ?? '',
                    'Заказ, id'           => $model->order?->id ?? '',
                    'Заказ, наименование' => $model->order?->name ?? '',
                    'Статус'              => $model->status->title(),
                    'Стоимость продуктов' => $cast->sumProducts ?: $cast->sumProductsAll,
                    'Стоимость доставки'  => $cast->deliveryPriceFinal ?: $cast->deliveryPrice,
                    'Стоимость Итого'     => $cast->sumFinal ?: $cast->sumCanceled,
                    'Дата создания'       => $model->created_at->format('Y-m-d H:i:s'),
                    'agency'             => $model->user->agency,
                    'media_source'       => $model->user->media_source,
                    'campaign'           => $model->user->campaign,
                    'campaign_id'        => $model->user->campaign_id,
                    'af_status'          => $model->user->installType,
                    'af_cpi'             => $model->user->af_cpi,
                    'install_time'       => $model->user->install_time,
                    'click_time'         => $model->user->click_time,
                ];
            }),
        ];
    }

    public function authorizedToReplicate (Request $request)
    {
        return false;
    }

    public function authorizedToDelete (Request $request)
    {
        return false;
    }

    public function authorizedToUpdate (Request $request)
    {
        return false;
    }


    public function authorizedToView (Request $request)
    {
        return true;
    }
}
