<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Http\Resources\User\BannerResource;
use App\Models\Banner;
use App\Models\Catalog;
use App\Models\Product;
use App\Services\ActiveApi\Attributes\Title;
use Illuminate\Database\Eloquent\Builder;

#[
    Title('Баннеры', 'banners'),
]
class BannerController extends ApiController
{
    protected array $relations = [
        'bannerable',
    ];

    #[
        Title('Список', 'list'),
    ]
    public function list ()
    {
        $banners = Banner::whereHasMorph(
            'bannerable',
            [Catalog::class, Product::class],
            function (Builder $query) {
                $query->where('model_type', Catalog::class)
                    ->orWhere('model_type', Product::class)
                    ->whereHas('actuals');
            }
        )
            ->where('is_published', true)
            ->get();

        return $this->send(BannerResource::collection($banners));
    }
}
