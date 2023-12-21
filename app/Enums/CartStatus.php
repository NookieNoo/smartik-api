<?php

namespace App\Enums;

enum CartStatus: string
{
    case ACTIVE           = 'active';
    case CANCELED_USER    = 'canceled:user';
    case CANCELED_TIME    = 'canceled:time';
    case CANCELED_REPLACE = 'canceled:replace';
    case DONE             = 'done';

    public function title (): string
    {
        return match ($this) {
            self::ACTIVE           => 'Активна',
            self::CANCELED_USER    => 'Отмена пользователем',
            self::CANCELED_TIME    => 'Отмена за времени',
            self::CANCELED_REPLACE => 'Отмена заменой',
            self::DONE             => 'Завершена',
        };
    }

    public function style (): string
    {
        return match ($this) {
            self::ACTIVE           => 'info',
            self::DONE             => 'success',
            self::CANCELED_USER,
            self::CANCELED_TIME,
            self::CANCELED_REPLACE => 'error',
        };
    }
}