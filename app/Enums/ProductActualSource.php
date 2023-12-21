<?php

namespace App\Enums;

enum ProductActualSource: string
{
    case PRICE = 'price';
    case CART  = 'cart';
    case STOCK = 'stock';
}