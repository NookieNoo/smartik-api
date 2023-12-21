<?php

namespace App\Nova;

use App\Enums\OrderStatus;
use App\Nova\UserPushToken;
use App\Nova\Filters\UserAuth;
use App\Nova\Metrics\ActiveUsers;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Http\Request;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    public static $perPageOptions = [50, 100, 150];

    public static $with = ['phone', 'orders', 'carts'];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static function label ()
    {
        return "Клиенты";
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email', 'phone.value'
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

            //Images::make('Аватар', 'avatar')->conversionOnIndexView('thumb'),

            Text::make('Имя', 'name')->sortable(),
            Text::make('Почта', 'email'),
            Text::make('Телефон', 'phone.value')->readonly(),
            Text::make('Заказов', 'orders_count')->resolveUsing(function () {
                $good = $this->orders->filter(fn ($item) => $item->status === OrderStatus::DONE)->count();
                $all = $this->orders->count();

                return '<span>' . $good . ' (' . $all . ')</span>';
            })->asHtml()->sortable(),
            Text::make('Корзин', 'carts_count')->resolveUsing(function () {
                return '<span>' . $this->carts->count() . '</span>';
            })->asHtml()->sortable(),
            Text::make('Последний вход', 'last_active_at')->onlyOnIndex()->withDateFormat('d.m.Y H:i:s')->sortable(),
            Date::make('Дата регистрации', 'created_at')->onlyOnIndex()->withDateFormat('d.m.Y H:i:s')->sortable(),
//            Text::make('Органик?', 'install_type')->onlyOnDetail(),
            Text::make('af_status', 'install_type')->hideWhenCreating()->hideWhenUpdating(),
            Text::make('media_source', 'media_source')->onlyOnDetail(),
            Text::make('Campaign', 'campaign')->onlyOnDetail(),
            Text::make('Agency', 'agency')->onlyOnDetail(),
            Text::make('Пригласил пользователей', 'invitedUsers')->resolveUsing(function () {
                $users = $this->invitedUsers;
                if ($users->isEmpty()) return 0;
                $str = $users->implode(function (\App\Models\User $item) {
                    $name = $item->name ?? 'Без имени';
                    $phone = $item->phone->value;
                    $id = $item->id;
                    return "<li><a class='link-default' href='/nova/resources/users/$id'>$name $phone</a></li>";
                }, '');

                return "<ol>" . $str . "</ol>";
            })->asHtml()->onlyOnDetail(),
            HasMany::make('Push токены', 'push_tokens', UserPushToken::class),
            HasMany::make('Заказы', 'orders', Order::class)
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
            ActiveUsers::make(),
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
            new UserAuth
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
        ini_set('max_execution_time', '600');
        return [
            ExportAsCsv::make()->withFormat(function ($model) {
                return [
                    'Пользователь, id'   => $model->id,
                    'Имя'               => $model->name,
                    'Почта'             => $model->email,
                    'Телефон'           => $model->phone?->value ?? '',
                    'Заказов выполнено' => $model->orders->filter(fn ($item) => $item->status === OrderStatus::DONE)->count(),
                    'Заказов всего'     => $model->orders->count(),
                    'Корзин всего'      => $model->carts->count(),
                    'Последний вход'    => $model->last_active_at->format('Y-m-d H:i:s'),
                    'Дата регистрации'  => $model->created_at->format('Y-m-d H:i:s'),
                    'agency'            => $model->agency,
                    'media_source'      => $model->media_source,
                    'campaign'          => $model->campaign,
                    'campaign_id'       => $model->campaign_id,
                    'af_status'         => $model->installType,
                    'af_cpi'            => $model->af_cpi,
                    'install_time'      => $model->install_time,
                    'click_time'        => $model->click_time,
                ];
            }),
        ];
    }

    public static function authorizedToCreate (Request $request): bool
    {
        return false;
    }

    public function authorizedToReplicate (Request $request): bool
    {
        return false;
    }

    public static function indexQuery (NovaRequest $request, $query)
    {
        // adds a `tags_count` column to the query result based on
        // number of tags associated with this product
        return $query->withCount('orders', 'carts');
    }
}
