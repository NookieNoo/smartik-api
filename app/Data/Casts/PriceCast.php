<?php

namespace App\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\DataProperty;

class PriceCast implements Cast
{

    public function cast (DataProperty $property, mixed $value, array $context): float
    {
        return number_format($value, 2, '.', '');
    }
}