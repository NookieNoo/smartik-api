<?php

namespace App\Enums;

enum PromocodeType: string
{
    case VALUE    = 'value';
    case PERCENT  = 'percent';
    case DELIVERY = 'delivery';

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
            self::VALUE    => 'Фиксированная сумма',
            self::PERCENT  => 'Процент от корзины',
            self::DELIVERY => 'Бесплатная доставка',
        };
    }
}