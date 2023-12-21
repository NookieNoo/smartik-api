<?php

namespace App\Jobs\SDG;

use App\Enums\ProductActualSource;
use App\Exceptions\Integration\SDG\ParseARVIntegrationNotFooundException;
use App\Exceptions\Integration\SDG\ParseARVNoProductException;
use App\Models\Integration;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Provider;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use App\Services\SDGTransport;
use App\Services\ShowcaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SDGProcessARVJob
{
    public function __construct (
        public string $file,
        public string $remote
    ) {}

    public function handle (SDGTransport $transport)
    {
        $xml = simplexml_load_string(file_get_contents($this->file), "SimpleXMLElement", LIBXML_NOCDATA);

        DB::beginTransaction();
        try {
            foreach ($xml->HEAD as $head) {
                $showcase = new ShowcaseService();

                $integration = Integration::query()
                    ->where('type', ImapXlsType::INBOUND)
                    ->where('data->_attributes->ORDNR', $head->attributes()->ORDER_NUM)
                    ->orderByDesc('id')
                    ->first();


                if (!$integration) {
                    throw new ParseARVIntegrationNotFooundException('Parse ARV, no integration', (array)$head);
                }

                $final = Integration::find($integration->parent_id);
                $provider = Provider::find($integration->provider_id);

                Integration::create([
                    'parent_id'   => $integration->id,
                    'date'        => Carbon::now(),
                    'type'        => ImapXlsType::ARV,
                    'provider_id' => $integration->provider_id,
                    'data'        => (array)$xml,
                ]);

                foreach ($head->children()->LINE as $line) {

                    $product = Product::find($line->attributes()->MATNR);
                    if (!$product) {
                        throw new ParseARVNoProductException('Parse ARV, no product', (array)$line);
                    }

                    // createQuietly нужен, чтобы не дёргать Observer. Всё это надо переделать, т.к.
                    // поменялась основная логика приложения.
                    $final_find = (object)collect($final->data)->where('ean', $product->eans()->first()->ean)->first();
                    if (!empty((array) $final_find)) { //Не пустой объект
                        $product_price = ProductPrice::create([
                            'product_id'      => $product->id,
                            'provider_id'     => $integration->provider_id,
                            'date'            => now(),
                            'count'           => $line->attributes()->MMENG,
                            'price'           => $final_find->finish_price * (1 + ($provider->margin / 100)),
                            'start_price'     => $final_find->rrc ?: $product->price ?: 1,
                            'finish_price'    => $final_find->finish_price,
                            'manufactured_at' => Carbon::createFromFormat("Ymd", $line->attributes()->PRODBBDDT),
                            'expired_at'      => Carbon::createFromFormat("Ymd", $line->attributes()->BBDDT),
                        ]);
                        $showcase->add($product_price, ProductActualSource::STOCK, (int)$line->attributes()->MMENG);
                    } else {
                        $product_price = ProductPrice::create([
                            'product_id'      => $product->id,
                            'provider_id'     => $integration->provider_id,
                            'date'            => now(),
                            'count'           => $line->attributes()->MMENG,
                            'price'           => 1,
                            'start_price'     => 1,
                            'finish_price'    => 1,
                            'manufactured_at' => Carbon::createFromFormat("Ymd", $line->attributes()->PRODBBDDT),
                            'expired_at'      => Carbon::createFromFormat("Ymd", $line->attributes()->BBDDT),
                        ]);
                        $showcase->add($product_price, ProductActualSource::STOCK, (int)$line->attributes()->MMENG, true);
                    }
                }
            }
            DB::commit();
            $transport->move($this->remote, 'Out/Ok');
        } catch (\Exception $e) {
            DB::rollBack();
            $transport->move($this->remote, 'Out/Bad', 'error_' . rand(1000, 9999));
            throw $e;
        }
    }
}
