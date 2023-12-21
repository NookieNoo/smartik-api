<?php

namespace App\Providers;

use App\Nova\Banner;
use App\Nova\Cart;
use App\Nova\Order;
use App\Nova\ProductActual;
use App\Nova\Promo;
use App\Nova\Brand;
use App\Nova\Product;
use App\Nova\PromoTag;
use App\Nova\Provider;
use App\Observers\ProductActualObserver;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use App\Nova\User;
use App\Nova\Catalog;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot ()
    {
        parent::boot();
        Nova::style('app', resource_path('css/nova.css'));

        Nova::serving(function () {
            \App\Models\ProductActual::observe(ProductActualObserver::class);
        });

        Nova::mainMenu(function (Request $request) {
            return [
                //MenuSection::dashboard(Main::class)->icon('chart-bar'),

                MenuSection::make('Клиенты', [
                    MenuItem::resource(User::class)->withBadge(fn () => \App\Models\User::count()),
                ])->icon('user')->collapsable(),

                MenuSection::make('Контент', [
                    MenuItem::resource(Catalog::class)->withBadge(fn () => \App\Models\Catalog::count()),
                    MenuItem::resource(Brand::class)->withBadge(fn () => \App\Models\Brand::count()),
                    MenuItem::resource(Product::class)->withBadge(fn () => \App\Models\Product::count()),
                    MenuItem::resource(ProductActual::class)->withBadge(fn () => \App\Models\ProductActual::count()),
                    MenuItem::resource(Provider::class)->withBadge(fn () => \App\Models\Provider::count()),
                ])->icon('document-text')->collapsable(),

                MenuSection::make('Маркетинг', [
                    MenuItem::resource(Promo::class)->withBadge(fn () => \App\Models\Promo::count()),
                    MenuItem::resource(PromoTag::class)->withBadge(fn () => \App\Models\PromoTag::count()),
                    MenuItem::resource(Banner::class)->withBadge(fn () => \App\Models\Banner::count()),
                ])->icon('beaker')->collapsable(),

                MenuSection::make('Деньги', [
                    MenuItem::resource(Order::class)->withBadge(fn () => \App\Models\Order::count()),
                    MenuItem::resource(Cart::class)->withBadge(fn () => \App\Models\Cart::count()),
                ])->icon('currency-dollar')->collapsable(),
            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes ()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate ()
    {
        Gate::define('viewNova', function ($user) {
            return true;
        });

    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards ()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools ()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register ()
    {
        //
    }
}
