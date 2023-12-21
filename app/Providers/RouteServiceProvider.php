<?php

namespace App\Providers;

use App\Http\Middleware\ExternalAuthMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot ()
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            Route::middleware('api')
                ->group(base_path('routes/api.php'));

            Route::prefix('media')
                ->group(base_path('routes/media.php'));

            Route::prefix('file')
                ->group(base_path('routes/file.php'));

            Route::prefix('provider')
                ->group(base_path('routes/provider.php'));

            Route::prefix('payment')
                ->group(base_path('routes/payment.php'));

            Route::prefix('kkm')
                ->group(base_path('routes/kkm.php'));

            Route::prefix('external')
                ->middleware(ExternalAuthMiddleware::class)
                ->group(base_path('routes/external.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting ()
    {
        /*RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });*/

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(180)->by($request->user()?->id ?? $request->ip());
        });
        
        RateLimiter::for('sms', function (Request $request) {
            return [
                Limit::perMinute(config('app.config.throttle_sms_ip', 10))->by($request->ip()),
                Limit::perMinute(config('app.config.throttle_sms_phone', 10))->by($request->input('phone')),
            ];
        });
    }
}
