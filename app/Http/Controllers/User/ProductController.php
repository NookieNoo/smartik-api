<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Http\Requests\User\UserProductActualListRequest;
use App\Http\Requests\User\UserProductSearchRequest;
use App\Http\Resources\User\ProductFullResource;
use App\Http\Resources\User\ShowcaseResource;
use App\Http\Resources\User\ShowcaseCollectionResource;
use App\Http\Responses\Response;
use App\Http\Sorts\Product\NewlySort;
use App\Http\Sorts\Product\PopularSort;
use App\Http\Sorts\Product\PriceSort;
use App\Models\Product;
use App\Models\Catalog;
use App\Models\ProductActual;
use App\Models\ProductPrice;
use App\Services\ActiveApi\Attributes\Position;
use App\Services\ActiveApi\Attributes\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

#[
    Title('Продукты', 'product'),
]
class ProductController extends ApiController
{
    #[
        Title('Список', 'list'),
    ]
    public function list (UserProductActualListRequest $request)
    {
        $limit = $request->input('limit') ?? 10;
        $offset = $request->input('offset') ?? 0;
        $order = $request->input('order') ?? 'discount';

        $query = ProductActual::query()
            ->with(['product', 'product_price', 'catalogs' => ['ancestors']])
            ->leftJoin('product_prices', 'product_prices.id', 'product_actuals.product_price_id')
            ->leftJoin('products', 'products.id', 'product_actuals.product_id')
            ->whereHas('product_price')
            ->whereHas('product')
            ->select('product_actuals.*');

        $expired_at_from = $request->input('expired_at_from'); //TODO refactor
        if ($expired_at_from) $query->whereDate('product_prices.expired_at', '>=', $expired_at_from);

        $sort = match ($order) {
            'newly' => AllowedSort::custom('product_actuals.created_at', new NewlySort()),
            '-newly' => AllowedSort::custom('-product_actuals.created_at', new NewlySort()),
            'price' => 'product_actuals.price',
            '-price' => '-product_actuals.price',
            'name' => 'products.name',
            '-name' => '-products.name',
            'discount' => '-product_actuals.discount_percent',
            'expired_at' => 'product_prices.expired_at',
            '-expired_at' => '-product_prices.expired_at',
            default => '-product_actuals.discount_percent',
        };
        $productActuals = QueryBuilder::for($query)
            ->allowedSorts([
//                AllowedSort::custom('popular', new PopularSort()),
            ])
            ->defaultSort($sort)
            ->paginate($limit, ['*'], 'products', $offset);

        return new ShowcaseCollectionResource($productActuals);
    }

    #[
        Title('Инфо', 'info'),
    ]
    public function info (Product $product)
    {
        $product->load(['catalogs', 'actuals' => [
            'product_price'
        ]]);

        return $this->send(new ProductFullResource($product));
    }

    #[
        Title('Рекомендуемые', 'recommended'),
    ]
    public function recommended ()
    {
        $products = ProductActual::query()
            ->with(['product', 'product_price', 'catalogs' => ['ancestors']])
            ->whereHas('product_price')
            ->whereHas('product')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $products = $products->sortBy('product_price.expired_at');

        return $this->send(array_values(ShowcaseResource::collection($products->groupBy('product.uuid'))->resolve()));
    }

    #[
        Title('Поиск', 'search'),
    ]
    public function search (UserProductSearchRequest $request)
    {
        $search = Product::search($request->input('query'))->get();

        $products = ProductActual::query()
            ->with(['product', 'product_price', 'catalogs' => ['ancestors']])
            ->leftJoin('product_prices', 'product_prices.id', 'product_actuals.product_price_id')
            ->whereHas('product_price')
            ->whereHas('product', function ($query) use ($search) {
                $query->whereIn('id', $search->pluck('id'));
            })
            ->orderBy('product_prices.expired_at')
            ->get();

        return $this->send(array_values(ShowcaseResource::collection($products->groupBy('product.uuid'))->resolve()));
    }

    #[
        Title('Выгрузка рекламных фидов', 'export_product_actuals_feed'),
    ]
    public function exportProductActualsFeed()
    {
        $result = [[
            'Название',
            'Скидка процент',
            'Фото',
            'РРЦ',
            'Цена',
            'Описание',
            'Состав',
            'Категории'
        ]];

        ProductActual::where('hidden', 0)
            ->each(function($actual) use (&$result) {
                $product_price = ProductPrice::find($actual->product_price_id);
                $result[] = [
                    str_replace('"', '""', $actual->product->name),
                    $actual->discount_percent,
                    url("/media/product/" . $actual->product->uuid . '/big'),
                    $product_price->start_price,
                    $product_price->price,
                    $actual->product->description,
                    $actual->product->compound,
                    $actual->product->catalogs->implode('name', ', '),
                ];
            });

        return (new FastExcel($result))
            ->withoutHeaders()
            ->download(date('Y-m-d') . "_export_product_actuals_feed.csv");
    }
}
