<?php

namespace App\Nova;

use App\Enums\OrderSystemStatus;
use App\Enums\PromocodeType;
use App\Nova\Actions\ExportSoldOrdersReport;
use App\Nova\Filters\OrderStatus;
use App\Services\Showcase\CartService;
use Illuminate\Http\Request;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\HasOneThrough;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaSortable\Traits\HasSortableRows;

class Order extends Resource
{
    use HasSortableRows;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Order::class;

    public static $with = [];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Заказы";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'user.phone.value'
    ];


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
            Text::make('Наименование', 'name'),
            BelongsTo::make('Пользователь', 'user', User::class)->displayUsing(function ($model) {
                return implode(', ', collect([
                    $model->name ?? "Без имени", $model->phone?->value
                ])->filter()->toArray());
            }),
            Text::make('Промокод')->resolveUsing(function () {
                $promo = $this->cart?->promos?->last();
                if (!$promo) return '--';
                return '<a class="link-default" href="/nova/resources/promos/' . $promo->id . '">' . $promo->code;
            })->asHtml(),
            Text::make('Стоимость промокода')->resolveUsing(function () {
                return $this->cart?->cast()->promoDiscount ?? 0;
            }),
            Text::make('Доставка')->resolveUsing(function () {
                $cast = $this->cart?->cast();
                if (!$cast || in_array($this->status, [
                        \App\Enums\OrderStatus::CREATED,
                        \App\Enums\OrderStatus::PAYMENT_PROBLEM,
                    ])) return '--';

                if (!$cast->deliveryPriceFinal) {
                    $result = "бесплатно";
                    if ($cast->deliveryCancelByCanceled) {
                        $result .= "<br />(часть товара отменено)";
                    }
                    if ($cast->deliveryCancelByPromo) {
                        $result .= "<br />(промокод)";
                    }
                } else {
                    $result = $cast->deliveryPriceFinal . "р";
                }


                return $result;
            })->asHtml(),
            Text::make('Сумма', 'sum_final')->resolveUsing(function () {
                return number_format($this->cart ? $this->cart->cast()->sumFinal : $this->sum_final, 2, '.', ' ') . ' &#8381;';
            })->asHtml()->textAlign('right')->sortable(),
            Text::make('af_status', 'user.install_type')->hideWhenCreating()->hideWhenUpdating(),
            Text::make('media_source', 'user.media_source')->onlyOnDetail(),
            Text::make('Campaign', 'user.campaign')->onlyOnDetail(),
            Text::make('Agency', 'user.agency')->onlyOnDetail(),
            Text::make('Дата заказа', function () {
                return $this->created_at->format('d.m.Y H:i:s');
            })->textAlign('right'),
            Text::make('Дата доставки', function () {
                return $this->delivery_at->format('d.m.Y');
            })->textAlign('right'),
            Text::make('Статус', function () {
                return '<span class="toasted ' . $this->status->style() . '">' . $this->status->title() . '</span>';
            })->asHtml(),
            Text::make('Адрес', function () {
                return $this->extra['address']['address_full'] ?? $this->extra['address']['address'];
            })->hideFromIndex(),
            Text::make('Ссылка в приложение', function () {
                $link = 'smartik://order/' . $this->uuid;
                return '<a class="link-default" href="' . $link . '"> ' . $link . '</a>';
            })->asHtml()->hideFromIndex(),
            Text::make('Комментарий', 'comment')->hideFromIndex(),
            HasManyThrough::make('Состав', 'cart_products', CartProduct::class),
            Text::make('Окно доставки', function () {
                if ($this->time_delivery_slot !== 0 && $this->time_delivery_slot !== 1){
                    return CartService::$time_delivery[0][0] . ':00-' . CartService::$time_delivery[1][1] . ':00';
                } else {
                    return CartService::$time_delivery[$this->time_delivery_slot][0] . ':00-' . CartService::$time_delivery[$this->time_delivery_slot][1] . ':00';
                }
            })->hideWhenCreating()->hideWhenUpdating(),
            Boolean::make('Есть заморозка', 'hasFrozenProducts()')->resolveUsing(function () {
                return $this->hasFrozenProducts();
            })->onlyOnIndex(),
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
            new OrderStatus()
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
        ini_set('max_execution_time', '90');
        return [
            ExportAsCsv::make()->withFormat(function ($model) {
                return [
                    'id'                 => $model->id,
                    'uuid'               => $model->uuid,
                    'Наименование'       => $model->name,
                    'Пользователь, id'   => $model->user ? $model->user->id : 'Пользователь не указан или удален',
                    'Пользователь, имя'  => $model->user?->name ?? 'Без имени',
                    'Промокод'           => $model->cart?->promos?->last()->code ?? '',
                    'Стоимость промокода'=> $model->cart?->cast()->promoDiscount ?? 0,
                    'Стоимость доставки' => $model->cart?->cast()->deliveryPriceFinal ?? 0,
                    'Сумма'              => $model->cart?->cast()->sumFinal ?? 0,
                    'Дата заказа'        => $model->created_at->format('Y-m-d H:i:s'),
                    'Дата доставки'      => $model->delivery_at->format('Y-m-d'),
                    'Статус'             => $model->status->title(),
                    'Адрес'              => $model->extra ? ($model->extra['address']['address_full'] ?? $model->extra['address']['address']) : 'Не указан',
                    'Комментарий'        => $model->comment,
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
            ExportSoldOrdersReport::make()
        ];
    }

    public static function authorizedToCreate (Request $request): bool
    {
        return false;
    }

    public function authorizedToReplicate (Request $request)
    {
        return false;
    }

    public function authorizedTo (Request $request, $ability)
    {
        return match ($ability) {
            'view'  => true,
            default => false
        };
    }
}
