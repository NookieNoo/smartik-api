<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\Route;

Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::post('signup', 'signup');
});

Route::controller(ProfileController::class)->group(function () {
    Route::post('af_data_conversion', 'saveAfCallbackData');
    Route::post('af_data_conversion_temp', 'saveAfCallbackDataTemp');
    Route::post('af_event', 'saveAfEvent');
});

Route::controller(OrderController::class)->group(function () {
    //FIXME Подумать как скрыть это
    Route::get('sold_orders_report', 'soldOrdersReport');
});
Route::controller(ProductController::class)->group(function () {
    //FIXME Подумать как скрыть это
    Route::get('export_product_actuals_feed', 'exportProductActualsFeed');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('logout', 'logout');
        Route::post('logout/all', 'logout_all');
        Route::get('info', 'info');
        Route::post('update', 'update');
        Route::post('settings', 'settings');
        Route::post('avatar', 'avatar');
        Route::post('sms/request', 'sms_request')->middleware('throttle:sms');
        Route::post('sms/activate', 'sms_activate');
        Route::post('address', 'address');
        Route::post('address/{user_address:uuid}/remove', 'remove_address');
        Route::post('push_token', 'create_push_token');
        Route::post('push_token/remove', 'remove_push_token');
        Route::post('push', 'push_sample');
        Route::get('promo', 'promo');
        Route::get('payment_cards', 'paymentCards');
        Route::delete('card/{id}', 'deleteCard');
    });

    Route::prefix('catalog')->controller(CatalogController::class)->group(function () {
        Route::get('list', 'list');
        Route::get('', 'list');
        Route::get('{catalog:uuid}/products', 'products');
    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::get('', 'list');
        Route::get('recommended', 'recommended');
        Route::get('{product:uuid}', 'info');
        Route::post('search', 'search');
    });

    Route::prefix('brand')->controller(BrandController::class)->group(function () {
        Route::get('', 'list');
        Route::get('{brand:slug}/products', 'products');
    });

    Route::prefix('cart')->controller(CartController::class)->group(function () {
        Route::get('info', 'info');
        Route::get('check', 'check');
        Route::delete('', 'clear');
        Route::post('upsert', 'upsert');
        Route::post('remove', 'remove');
        Route::post('promo', 'promo');
        Route::delete('promo', 'removePromo');
        Route::post('change_count/{product_price:uuid}', 'changeCount');
    });

    Route::prefix('order')->controller(OrderController::class)->group(function () {
        Route::post('', 'create');
        Route::get('list/{uuid}', 'list');
        Route::get('{order:uuid}', 'info');
        Route::post('{order:uuid}/cancel', 'cancel');
        Route::get('{order:uuid}/payments', [PaymentController::class, 'list']);
        Route::get('{order:uuid}/payment', [PaymentController::class, 'info']);
        Route::post('{order:uuid}/payment/create', [PaymentController::class, 'create']);
        Route::post('{order:uuid}/payment/cancel', [PaymentController::class, 'cancel']);
        Route::get('statuses', 'orderStatuses');
    });

    Route::prefix('favorite')->controller(FavoriteController::class)->group(function () {
        Route::get('{model}', 'list');
        Route::post('add/{model}/{uuid}', 'add');
        Route::post('remove/{model}/{uuid}', 'remove');
    });

    Route::prefix('banner')->controller(BannerController::class)->group(function () {
        Route::get('', 'list');
    });
});


