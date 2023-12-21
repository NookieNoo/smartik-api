<?php

namespace App\Observers;

use App\Models\ProductActual;
use App\Models\ProductPrice;
use App\Services\ShowcaseService;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class ProductActualObserver
{
    public function __construct (
        protected ShowcaseService $showcase
    ) {}

    public function creating (ProductActual $product)
    {
        Nova::whenServing(function (NovaRequest $request) use ($product) {
            // Only invoked during Nova requests...
            $product_price = ProductPrice::where([
                'provider_id'     => $request->input('provider'),
                'product_id'      => $request->input('product'),
                'manufactured_at' => $request->input('product_price_manufactured_at'),
                'expired_at'      => $request->input('product_price_expired_at'),
            ])->first();
            if (!$product_price) {
                $product_price = ProductPrice::create([
                    'provider_id'     => $request->input('provider'),
                    'product_id'      => $request->input('product'),
                    'manufactured_at' => $request->input('product_price_manufactured_at'),
                    'expired_at'      => $request->input('product_price_expired_at'),
                    'date'            => now(),
                    'count'           => $request->input('count'),
                    'price'           => $request->input('price'),
                    'start_price'     => $request->input('product_price_start_price'),
                    'finish_price'    => $request->input('product_price_finish_price'),

                ]);
            }
            $product->product_price_id = $product_price->id;

            if (!$product->discount) $product->discount = ShowcaseService::discountCalculate($product_price);
            if (!$product->discount_percent) $product->discount_percent = ShowcaseService::discountPercentCalculate($product_price);
        }, function (Request $request) use ($product) {
            // Invoked for non-Nova requests...
        });
    }

    public function updating (ProductActual $product)
    {
        Nova::whenServing(function (NovaRequest $request) use ($product) {
            // Only invoked during Nova requests...
            if ($request->input('provider')) {
                $product_price = ProductPrice::where([
                    'provider_id'     => $request->input('provider'),
                    'product_id'      => $request->input('product'),
                    'manufactured_at' => $request->input('product_price_manufactured_at'),
                    'expired_at'      => $request->input('product_price_expired_at'),
                ])->first();
                if (!$product_price) {
                    $product_price = ProductPrice::create([
                        'provider_id'     => $request->input('provider'),
                        'product_id'      => $request->input('product'),
                        'date'            => now(),
                        'count'           => $request->input('count'),
                        'price'           => $request->input('price'),
                        'start_price'     => $request->input('product_price_start_price'),
                        'finish_price'    => $request->input('product_price_finish_price'),
                        'manufactured_at' => $request->input('product_price_manufactured_at'),
                        'expired_at'      => $request->input('product_price_expired_at'),

                    ]);
                } else {
                    $product_price->update([
                        'price'        => $request->input('price'),
                        'start_price'  => $request->input('product_price_start_price'),
                        'finish_price' => $request->input('product_price_finish_price')
                    ]);
                }
                $product->product_price_id = $product_price->id;
                $product->discount = ShowcaseService::discountCalculate($product_price);
                $product->discount_percent = ShowcaseService::discountPercentCalculate($product_price);
            }
        }, function (Request $request) use ($product) {
            // Invoked for non-Nova requests...
        });
    }

    public function deleting (ProductActual $price)
    {
        //$this->showcase->removeFromCarts($price);
    }
}