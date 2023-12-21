<?php

namespace App\Enums;

enum CartProductStatus: string
{
    case CANCELED_ACTUAL   = 'canceled:actual';
    case CANCELED_PROVIDER = 'canceled:provider';
    case CANCELED_SDG      = 'canceled:sdg';
    case CANCELED_USER     = 'canceled:user';

    case START     = 'start';
    case ORDER     = 'order';
    case CONFIRM   = 'confirm';
    case WAREHOUSE = 'warehouse';
    case DELIVERY  = 'delivery';
    case DONE      = 'done';

    public static function canceled ()
    {
        return [
            self::CANCELED_ACTUAL,
            self::CANCELED_PROVIDER,
            self::CANCELED_SDG,
            self::CANCELED_USER
        ];
    }

    public static function isCanceled (?self $status = null): bool
    {
        if ($status && in_array($status, self::canceled())) {
            return true;
        }
        return false;
    }

    public function title (): string
    {
        return match ($this) {
            self::CANCELED_ACTUAL   => 'Отмена, не актуально',
            self::CANCELED_PROVIDER => 'Отмена провайдером',
            self::CANCELED_SDG      => 'Отмена SDG',
            self::CANCELED_USER     => 'Отмена пользователем',
            self::START             => 'В корзине',
            self::ORDER             => 'Заказано поставщику',
            self::CONFIRM           => 'Подтверждено поставщиком',
            self::WAREHOUSE         => 'На складе',
            self::DELIVERY          => 'В доставке',
            self::DONE              => 'Завершено',
        };
    }

    public function style (): string
    {
        return match ($this) {
            self::START         => 'info',
            self::DONE          => 'success',
            self::ORDER,
            self::CONFIRM,
            self::WAREHOUSE,
            self::DELIVERY      => 'warning',
            self::CANCELED_ACTUAL,
            self::CANCELED_PROVIDER,
            self::CANCELED_SDG,
            self::CANCELED_USER => 'error',
        };
    }
}