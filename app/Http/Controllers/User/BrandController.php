<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Http\Resources\User\BrandResource;
use App\Http\Resources\User\ProductFullResource;
use App\Http\Resources\User\ProductResource;
use App\Http\Resources\User\ShowcaseResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductActual;
use App\Services\ActiveApi\Attributes\Title;

#[
    Title('Бренды', 'brands'),
]
class BrandController extends ApiController
{
    protected array $relations = [];

    #[
        Title('Список', 'list'),
    ]
    public function list ()
    {
        return $this->send(BrandResource::collection(Brand::all()));
    }

    #[
        Title('Список товаров', 'products'),
    ]
    public function products (Brand $brand)
    {
        $actuals = ProductActual::query()
            ->with(['product', 'product_price', 'catalogs' => ['ancestors']])
            ->whereRelation('product', 'brand_id', $brand->id)
            ->whereHas('product_price')
            ->whereHas('product')
            ->get();

        $other = Product::query()
            ->whereNotIn('id', $actuals->pluck('product_id'))
            ->where('brand_id', $brand->id)
            ->with(['energy', 'actuals'])
            ->get();

        return $this->send([
            'actuals' => array_values(ShowcaseResource::collection($actuals->groupBy('product.uuid'))->resolve()),
            'other'   => ProductResource::collection($other),
            'brand'   => new BrandResource($brand)
        ]);
    }
}