<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CREATED            = 'created';
    case PAYMENT_PROCESS    = 'payment:process';
    case PAYMENT_DONE       = 'payment:done';
    case PAYMENT_PROBLEM    = 'payment:problem';
    case DELIVERY_CREATED   = 'delivery:created';
    case DELIVERY_PERFORMED = 'delivery:performed';
    case DELIVERY_ON_WAY    = 'delivery:on_way';
    case DELIVERY_ARRIVED   = 'delivery:arrived';
    case DONE               = 'done';
    case CANCELED_USER      = 'canceled:user';
    case CANCELED_DRIVER    = 'canceled:driver';
    case CANCELED_MANAGER   = 'canceled:manager';

    public static function canceled (): array
    {
        return [
            self::CANCELED_USER,
            self::CANCELED_DRIVER,
            self::CANCELED_MANAGER
        ];
    }

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
            self::CREATED            => 'Создан',
            self::PAYMENT_PROCESS    => 'В процессе оплаты',
            self::PAYMENT_DONE       => 'Оплачен',
            self::PAYMENT_PROBLEM    => 'Проблемы с оплатой',
            self::DELIVERY_CREATED   => 'Передан в доставку',
            self::DELIVERY_PERFORMED => 'У водителя',
            self::DELIVERY_ON_WAY    => 'Водитель едет на точку',
            self::DELIVERY_ARRIVED   => 'Водитель на точке',
            self::DONE               => 'Выполнен',
            self::CANCELED_USER      => 'Отменён клиентом',
            self::CANCELED_DRIVER    => 'Отменён водителем',
            self::CANCELED_MANAGER   => 'Отменён менеджером',
        };
    }

    public function style (): string
    {
        return match ($this) {
            self::CREATED          => 'info',
            self::PAYMENT_PROCESS,
            self::DELIVERY_CREATED,
            self::DELIVERY_PERFORMED,
            self::DELIVERY_ON_WAY,
            self::DELIVERY_ARRIVED => 'warning',
            self::PAYMENT_DONE,
            self::DONE             => 'success',
            self::PAYMENT_PROBLEM,
            self::CANCELED_USER,
            self::CANCELED_DRIVER,
            self::CANCELED_MANAGER => 'error',
        };
    }
}