<?php

namespace App\Providers;

use App\Models\ApiUser;
use App\Services\Gosnumber;
use App\Services\HttpParser\HttpParserInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register ()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot ()
    {
        Model::unguard();
        $user = auth()->user();

        if ($user && $user::class === ApiUser::class) {
            $this->app->bind(HttpParserInterface::class, function () use ($user) {
                if ($user->http_parser) {
                    return new $user->http_parser;
                }
            });
        }

        $this->app->bind('gosnumber', Gosnumber::class);
    }
}
