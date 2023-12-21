<?php

namespace App\Providers;

use App\Listeners\AdminSubscriber;
use App\Listeners\CartProductSubscriber;
use App\Listeners\ExternalAtsSubscriber;
use App\Listeners\OrderSubscrber;
use App\Models\Banner;
use App\Models\Catalog;
use App\Models\EventLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Product;
use App\Models\ProductActual;
use App\Models\ProductPrice;
use App\Models\Promo;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPayment;
use App\Models\UserPromo;
use App\Observers\BannerObserver;
use App\Observers\CatalogObserver;
use App\Observers\EventLogObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentLogObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductActualObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductPriceObserver;
use App\Observers\PromoObserver;
use App\Observers\ProviderObserver;
use App\Observers\UserAddressObserver;
use App\Observers\UserObserver;
use App\Observers\UserPaymentObserver;
use App\Observers\UserPromoObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    protected $subscribe = [
        OrderSubscrber::class,
        AdminSubscriber::class,
        CartProductSubscriber::class,
        ExternalAtsSubscriber::class
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot ()
    {
        User::observe(UserObserver::class);
        Catalog::observe(CatalogObserver::class);
        Product::observe(ProductObserver::class);
        Provider::observe(ProviderObserver::class);
        ProductPrice::observe(ProductPriceObserver::class);
        ProductActual::observe(ProductActualObserver::class);
        UserAddress::observe(UserAddressObserver::class);
        UserPayment::observe(UserPaymentObserver::class);
        Order::observe(OrderObserver::class);
        Payment::observe(PaymentObserver::class);
        PaymentLog::observe(PaymentLogObserver::class);
        EventLog::observe(EventLogObserver::class);
        Promo::observe(PromoObserver::class);
        UserPromo::observe(UserPromoObserver::class);
        Banner::observe(BannerObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents ()
    {
        return false;
    }
}
