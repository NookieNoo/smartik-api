<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ShowcaseService;
use App\Traits\WithUuid;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class ProductPriceObserver
{
    use WithUuid;

    public function __construct (
        protected ShowcaseService $showcase
    ) {}

    public function created (ProductPrice $price)
    {
        Nova::whenServing(function (NovaRequest $request) use ($price) {}, function (Request $request) use ($price) {
            // новая логика, вроде как это не требуется
            //$this->showcase->add($price);
        });
    }
}