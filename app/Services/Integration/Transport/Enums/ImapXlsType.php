<?php

namespace App\Services\Integration\Transport\Enums;

enum ImapXlsType: string
{
    case CATALOG             = 'catalog';
    case PRICES              = 'prices';
    case TO_PROVIDER         = 'to_provider';
    case FINAL_FROM_PROVIDER = 'final';
    case INBOUND             = 'inbound';
    case ARV                 = 'arv';
    case OUTBOUND            = 'outbound';
    case SHP                 = 'shp';
    case WBL                 = 'wbl';

    public function title (): string
    {
        return match ($this) {
            self::CATALOG             => 'Обновление каталога',
            self::PRICES              => 'Остатки',
            self::TO_PROVIDER         => 'Файл для поставщика',
            self::FINAL_FROM_PROVIDER => 'Ответ поставщика о количестве',
            self::INBOUND             => 'Входящие для СДГ',
            self::ARV                 => 'Отклик о приходе от СДГ',
            self::OUTBOUND            => 'Заказы для СДГ',
            self::SHP                 => 'Отклик о сборке от СДГ',
            self::WBL                 => 'Данные о курьере от СДГ',
        };
    }
}