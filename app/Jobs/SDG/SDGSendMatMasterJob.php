<?php

namespace App\Jobs\SDG;

use App\Models\Product;
use Spatie\ArrayToXml\ArrayToXml;

class SDGSendMatMasterJob extends SDGJob
{
    protected string $type = 'Matmaster';

    public function generate (): string|false
    {
        $items = collect($this->extra['report'])->map(function ($item) {
            $product = Product::findOrFail($item['product_id']);
            $weight = match ($product->weight_type) {
                'g',
                'ml'    => $product->weight,
                'l',
                'kg'    => $product->weight * 1000,
                default => 1000
            };

            return (object)[
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'weight'              => $weight,
                'product_ean'         => $product->eans()->first()->ean,
                'product_marked'      => !!$product->marked,
                'product_expire_days' => $product->expire_days,
            ];
        });

        $result = array_values($items->map(function ($item) {
            return [
                '_attributes' => [
                    'CCODE'     => config('sdg.ccode'),
                    'MATNR'     => (string)$item->product_id,
                    'MAKTX'     => (string)$item->product_name,
                    'MEINH'     => 'PCE',
                    'BWEGT'     => (string)$item->weight,
                    'NWEGT'     => (string)$item->weight,
                    'SCODE'     => (string)$item->product_ean ?? "",
                    'MARKED'    => $item->product_marked ? "true" : "false",
                    'SHELFLIFE' => $item->product_expire_days
                ]
            ];
        })->toArray());

        $xml = new ArrayToXml(['MITEM' => $result], [
            'rootElementName' => 'MATMASTER'
        ], true, "windows-1251");

        return $xml->prettify()->toXml();
    }
}