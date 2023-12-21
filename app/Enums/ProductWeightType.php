<?php

namespace App\Enums;

enum ProductWeightType: string
{
    case COUNT = 'count';
    case ML    = 'ml';
    case L     = 'l';
    case G     = 'g';
    case KG    = 'kg';

    public static function titles ()
    {
        $result = [];

        foreach (self::cases() as $type) {
            $result[$type->value] = $type->title();
        }

        return $result;
    }

    public function title (): string
    {
        return match ($this) {
            self::COUNT => 'шт',
            self::ML    => 'мл',
            self::L     => 'л',
            self::G     => 'г',
            self::KG    => 'кг',
        };
    }
}