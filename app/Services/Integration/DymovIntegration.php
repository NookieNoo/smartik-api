<?php

namespace App\Services\Integration;

use App\Enums\ProductWeightType;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderProduct;
use App\Models\ProductEan;
use App\Services\Integration\Transport\ImapXlsTransport;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DymovIntegration extends ImapXlsTransport
{

    protected int $satisfyCount     = 0;
    protected int $satisfyExireDays = 1;

    public function satisfyPrice (float $rrc): float
    {
        return 10000000;
    }

    public function __construct ()
    {
        $this->provider = Provider::where('slug', 'Dymov')->first();

        $this->isCatalog = [
            'a4' => 'Код',
            'e4' => 'НДС',
            'f4' => 'ШТРИХ КОД',
            'h4' => 'вес 1 шт, кг',
            'i4' => 'Средняя розничная цена'
        ];

        $this->isPrices = [
            'a1' => 'Распродажа',
            'b4' => 'Код',
            'f4' => 'Дата производства',
            'l4' => 'Объем КГ',
            'o4' => 'руб/ед с НДС',
        ];

        $this->parseCatalog = [
            'skip' => 5,
            'ean'  => 'f',
            'data' => [
                'external_id' => 'a',
                'expire_days' => function (int $position): int {
                    $date = $this->getExcelValue(cell: "c" . $position, formatted: true);
                    return $date ? Carbon::createFromFormat('m/d/Y', $date)
                        ->diffInDays(Carbon::createFromFormat('m/d/Y', $this->getExcelValue(cell: "d" . $position, formatted: true))) : 30;
                },
                'vat'         => function (int $position): int {
                    return (int)$this->getExcelValue(cell: "e" . $position);
                },
                'weight_type' => function () {
                    return ProductWeightType::KG;
                },
                'weight'      => 'h:float',
                'price'       => 'i:price'
            ]
        ];

        $this->parsePrices = [
            'skip' => 6,
            'ean'  => function (int $position) {
                $external_id = $this->getExcelValue(cell: "b" . $position);
                $find = ProviderProduct::where('provider_id', $this->provider->id)->where('external_id', $external_id)->first();
                if ($find) {
                    return ProductEan::where('product_id', $find->product_id)->first()->ean;
                }
                return "0";
            },
            'data' => [
                'expired_at'      => function (int $position) {
                    return Carbon::createFromFormat('m/d/Y', $this->getExcelValue(cell: "i" . $position, formatted: true))
                        ->format('Y-m-d');
                },
                'manufactured_at' => function (int $position) {
                    return Carbon::createFromFormat('m/d/Y', $this->getExcelValue(cell: "f" . $position, formatted: true))
                        ->format('Y-m-d');
                },
                'count'           => function (int $position, Product $product) {
                    return floor($this->getExcelValue(cell: "l" . $position) / $product->weightKg ?? 1);
                },
                'finish_price'    => 'o:price'
            ]
        ];

        $this->sendPrices = [
            ['p4' => 'заказ, шт'],
            ['q4' => 'готовы поставить, шт'],
            function (array $result) {
                $size = $this->excel->getSheet(0)->getHighestRow();
                for ($i = 0; $i < $size; $i++) {
                    foreach ($result as $v) {
                        if ($this->getExcelValue(cell: "b" . $i) == $v['external_id'] && $this->getExcelValue(cell: "i" . $i, formatted: true) == Carbon::parse($v['expired_at'])->format('n/j/Y')) {
                            $this->excel->getSheet(0)->getCell("p" . $i)->setValue($v['count']);
                            $this->excel->getSheet(0)->getStyle('a' . $i . ':q' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE994');
                            $this->excel->getSheet(0)->getStyle('p' . $i . ':q' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        }
                    }
                }
            },
        ];
    }
}