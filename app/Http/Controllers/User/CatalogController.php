<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Http\Requests\User\UserListCatalogRequest;
use App\Http\Resources\User\CatalogResource;
use App\Http\Resources\User\ShowcaseResource;
use App\Models\Catalog;
use App\Models\ProductActual;
use App\Services\ActiveApi\Attributes\Position;
use App\Services\ActiveApi\Attributes\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[
    Title('Каталог', 'catalog'),
    Position(3)
]
class CatalogController extends ApiController
{
    #[
        Title('Список', 'list'),
    ]
    public function list ()
    {
        $catalogs = Catalog::defaultOrder()->get()->filter(fn ($item) => !$item->hidden && (!$item->parent || !$item->parent->hidden))->toTree();

        return $this->send(CatalogResource::collection($catalogs));
    }

    #[
        Title('Список товаров', 'products'),
    ]
    public function products (Catalog $catalog)
    {
        $catalogs = [$catalog->uuid, ...$catalog->descendants->pluck('uuid')];
        $all = ProductActual::query()
            ->with(['product', 'product_price', 'catalogs' => ['ancestors']])
            ->leftJoin('product_prices', 'product_prices.id', 'product_actuals.product_price_id')
            ->whereHas('catalogs', function ($query) use ($catalogs) {
                $query->whereIn('catalogs.uuid', $catalogs);
            })
            ->whereHas('product_price')
            ->orderBy('product_prices.expired_at')
            ->get();

        return $this->send([
            'items' => array_values(ShowcaseResource::collection($all->groupBy('product.uuid'))->resolve())
        ]);
    }
}
