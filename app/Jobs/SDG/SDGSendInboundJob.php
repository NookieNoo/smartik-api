<?php

namespace App\Jobs\SDG;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderSystemStatus;
use App\Models\Integration;
use App\Models\IntegrationReport;
use App\Models\Provider;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\ArrayToXml\ArrayToXml;

class SDGSendInboundJob extends SDGJob
{
    protected string $type = 'Inbound';

    public function generate (string $encoding = "windows-1251"): string
    {
        dispatch_sync(new SDGSendMatMasterJob(send: $this->send, extra: $this->extra));

        $provider = Provider::find($this->extra['integration']['provider_id']);
        $i = 0;

        $attributes = [
            'CCODE'    => config('sdg.ccode'),
            'CITYID'   => '000000854',
            'ORDNR'    => $this->extra['integration']->id,
            'DLVNR'    => $this->extra['integration']->id,
            'ORDTE'    => Carbon::now()->addDay()->format('Ymd'),
            'RMENG'    => (string)count($this->extra['data']),
            'VNAME'    => (string)$provider->name,
            'VCODE'    => (string)$provider->id,
            'MCOST'    => (string)0,
            'PRODUCER' => (string)$provider->name,
            'SUP_INN'  => (string)$provider->inn
        ];

        if ($provider->shipperpoint_id) {
            $attributes['DLVKD'] = 1;
            $attributes['SHIPPERPOINTID'] = $provider->shipperpoint_id;
        }


        $result = [
            '_attributes' => $attributes,
            'ORDRW'       => collect($this->extra['report'])->filter(function ($item) {
                return (int)$item['finish_count'] > 0;
            })->map(function ($item) use (&$i) {
                $item = (object)$item;
                return [
                    '_attributes' => [
                        'POSNR'     => (string)$i++,
                        'MATNR'     => (string)$item->product_id,
                        'MMENG'     => (string)($item->finish_count ?? 0),
                        'BBDDT'     => Carbon::parse($item->product_expired_at)->format('Ymd') ?? "",
                        'PRODBBDDT' => Carbon::parse($item->product_manufactured_at)->format('Ymd') ?? "",
                        'MEINH'     => 'PCE'
                    ]
                ];
            })->filter()->values()->toArray()
        ];

        $result['_attributes']['RMENG'] = (string)count($result['ORDRW']);

        Integration::create([
            'parent_id'   => $this->extra['integration']->id,
            'date'        => Carbon::now(),
            'type'        => ImapXlsType::INBOUND,
            'provider_id' => $this->extra['integration']->provider_id,
            'data'        => $result,
        ]);


        $xml = new ArrayToXml(['ORDHD' => $result], [
            'rootElementName' => 'INBNOTIFICATION'
        ], true, $encoding);

        return $xml->prettify()->toXml();
    }
}
