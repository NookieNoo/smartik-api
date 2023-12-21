<?php
namespace App\Http\Sorts\Product;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class PriceSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        dd();
        $direction = $descending ? 'DESC' : 'ASC';

        $query->orderByRaw("LENGTH(`{$property}`) {$direction}");
    }
}
