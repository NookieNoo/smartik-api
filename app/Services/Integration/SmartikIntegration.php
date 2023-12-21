<?php

namespace App\Services\Integration;

use App\Services\Integration\Transport\ImapXlsTransport;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SmartikIntegration extends ImapXlsTransport
{
    public function __construct ()
    {
        $this->isCatalog = [
            'a1' => 'каталог',
            'a2' => 'или нет',
        ];

        $this->isPrices = [
            function () {
                if ($this->getExcelValue(cell: "a1") === "Прайс") return true;
                return false;
            },
            'a4' => 'Артикул',
            'b4' => 'ШтрихКод',
            'c4' => 'Наименование',
            'd4' => 'Дата производства',
            'e4' => 'Годен до',
            'f4' => 'Вес (в граммах)',
            'g4' => 'НДС',
            'h4' => 'РРЦ',
            'i4' => 'Цена (для Правильных покупок)',
            'j4' => 'Количество вложений в коробку (шт)',
            'k4' => 'Количество (в штуках)',
            'l4' => 'Заказ (в штуках)',
            'm4' => 'Поставка (в штуках)',
            function () {
                $size = $this->excel->getSheet(0)->getHighestRow();
                for ($i = 5; $i < $size; $i++) {
                    if ($this->getExcelValue(cell: "l" . $i)) return false;
                }
                return true;
            }
        ];

        $this->isFinal = [
            'a4' => 'Артикул',
            'b4' => 'ШтрихКод',
            'c4' => 'Наименование',
            'd4' => 'Дата производства',
            'e4' => 'Годен до',
            'f4' => 'Вес (в граммах)',
            'g4' => 'НДС',
            'h4' => 'РРЦ',
            'i4' => 'Цена (для Правильных покупок)',
            'j4' => 'Количество вложений в коробку (шт)',
            'k4' => 'Количество (в штуках)',
            'l4' => 'Заказ (в штуках)',
            'm4' => 'Поставка (в штуках)',
        ];

        $this->parseCatalog = [
            'skip' => 0,
            'ean'  => 'a',
            'data' => []
        ];

        $this->parsePrices = [
            'skip' => 6,
            'ean'  => 'b:source',
            'data' => [
                'external_id'     => 'a',
                'name'            => 'c',
                'manufactured_at' => 'd:date',
                'expired_at'      => 'e:date',
                'weight'          => 'f',
                'nds'             => 'g',
                'rrc'             => 'h:price',
                'finish_price'    => 'i:price',
                'quant'           => 'j',
                'count'           => 'k',
            ]
        ];

        $this->parseFinal = [
            'skip' => 6,
            'ean'  => 'b',
            'data' => [
                'external_id'     => 'a',
                'name'            => 'c',
                'manufactured_at' => 'd:date',
                'expired_at'      => 'e:date',
                'weight'          => 'f',
                'nds'             => 'g',
                'rrc'             => 'h:price',
                'finish_price'    => 'i:price',
                'quant'           => 'j',
                'count'           => 'k',
                'need_count'      => 'l',
                'finish_count'    => 'm',
            ]
        ];

        $this->sendPrices = [
            ['a1' => 'Заказ'],
            function (array $result) {
                $size = $this->excel->getSheet(0)->getHighestRow();
                for ($i = 0; $i <= $size; $i++) {
                    foreach ($result as $v) {
                        if ($this->getExcelValue(cell: "b" . $i) == $v['ean'] &&
                            (
                                $this->getExcelValue(cell: "e" . $i, formatted: true) == Carbon::parse($v['expired_at'])->format('d.m.Y') ||
                                $this->getExcelValue(cell: "e" . $i, formatted: true) == Carbon::parse($v['expired_at'])->format('Y.m.d') ||
                                $this->getExcelValue(cell: "e" . $i, formatted: true) == Carbon::parse($v['expired_at'])->format('n/j/Y')
                            )
                        ) {
                            $this->excel->getSheet(0)->getCell("l" . $i)->setValue($v['count']);
                            $this->excel->getSheet(0)->getStyle('a' . $i . ':m' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE994');
                            $this->excel->getSheet(0)->getStyle('l' . $i . ':m' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        }
                    }
                }
            }
        ];
    }
}