<?php

namespace App\Services\Integration;

use App\Models\Product;
use App\Models\ProductEan;
use App\Models\Provider;
use App\Models\ProviderProduct;
use App\Services\Integration\Transport\ImapXlsTransport;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LactalisIntegration extends ImapXlsTransport
{
    public function __construct ()
    {
        $this->provider = Provider::where('slug', 'Lactalis')->first();

        $this->isCatalog = [
            'a6'  => 'СПЕЦИФИКАЦИЯ',
            'j10' => 'Штрих коды',
            'm10' => 'Срок годности',
            's10' => 'ставка НДС',
            't10' => 'розничная цена'
        ];

        $this->isPrices = [
            'l4' => 'Вес',
            'f6' => 'Код товара',
            'h6' => 'Срок годности',
            'j6' => 'Остаток на дату',
            'n6' => ['!=', 'заказ, шт']
        ];

        $this->isFinal = [
            'l4' => 'Вес',
            'f6' => 'Код товара',
            'h6' => 'Срок годности',
            'j6' => 'Остаток на дату',
            'n6' => 'заказ, шт',
            'o6' => 'готовы поставить, шт',
        ];

        $this->parseCatalog = [
            'skip' => 13,
            'ean'  => 'j',
            'data' => [
                'external_id' => 'a',
                'expire_days' => 'm:int',
                'vat'         => function (int $position): int {
                    return ((float)$this->getExcelValue(cell: "s" . $position)) * 100;
                },
                'price'       => 't:price',
                'extra'       => function (int $position): array {
                    return ['multiple' => (int)$this->getExcelValue(cell: "r" . $position)];
                }
            ]
        ];

        $this->parsePrices = [
            'skip' => 9,
            'ean'  => function (int $position) {
                $external_id = $this->getExcelValue(cell: "f" . $position);
                $find = ProviderProduct::where('provider_id', $this->provider->id)->where('external_id', $external_id)->first();
                if ($find) {
                    return ProductEan::where('product_id', $find->product_id)->first()->ean;
                }
                return "0";
            },
            'data' => [
                'expired_at'      => 'h:date',
                'manufactured_at' => function (int $position) {
                    return Carbon::parse($this->getExcelValue(cell: "h" . $position))
                        ->subDays($this->getExcelValue(cell: "j" . $position))
                        ->format('Y-m-d');
                },
                'name'            => 'g',
                'count'           => function (int $position, Product $product) {
                    return $this->getExcelValue(cell: "l" . $position) / $product->weightKg;
                },
                'finish_price'    => 'm:price'
            ]
        ];

        $this->parseFinal = [
            'skip' => 9,
            'ean'  => function (int $position) {
                $external_id = $this->getExcelValue(cell: "f" . $position);
                $find = ProviderProduct::where('provider_id', $this->provider->id)->where('external_id', $external_id)->first();
                if ($find) {
                    return ProductEan::where('product_id', $find->product_id)->first()->ean;
                }
                return "0";
            },
            'data' => [
                'expired_at'      => 'h:date',
                'manufactured_at' => function (int $position) {
                    return Carbon::parse($this->getExcelValue(cell: "h" . $position))
                        ->subDays($this->getExcelValue(cell: "j" . $position))
                        ->format('Y-m-d');
                },
                'name'            => 'g',
                'count'           => function (int $position, Product $product) {
                    return $this->getExcelValue(cell: "l" . $position) / $product->weightKg;
                },
                'finish_price'    => 'm:price',
                'need_count'      => 'n:int',
                'finish_count'    => 'o:int',
            ]
        ];

        $this->sendPrices = [
            ['n6' => 'заказ, шт'],
            ['o6' => 'готовы поставить, шт'],
            function (array $result) {
                $size = $this->excel->getSheet(0)->getHighestRow();
                for ($i = 0; $i < $size; $i++) {
                    foreach ($result as $v) {
                        if ($this->getExcelValue(cell: "f" . $i) == $v['external_id'] && $this->getExcelValue(cell: "h" . $i, formatted: true) == $v['expired_at']->format('d.m.Y')) {
                            $this->excel->getSheet(0)->getCell("n" . $i)->setValue($v['count']);
                            $this->excel->getSheet(0)->getStyle('a' . $i . ':o' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE994');
                            $this->excel->getSheet(0)->getStyle('n' . $i . ':o' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        }
                    }
                }
            }
        ];
    }
}