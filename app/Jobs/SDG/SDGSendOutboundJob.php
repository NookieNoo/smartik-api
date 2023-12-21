<?php

namespace App\Jobs\SDG;

use App\Enums\CartProductStatus;
use App\Enums\OrderSystemStatus;
use App\Events\System\SystemChangeSystemStatusOrderEvent;
use App\Models\Cart;
use App\Models\Integration;
use App\Models\UserAddress;
use App\Models\Order;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use App\Services\SDGTransport;
use App\Services\Showcase\CartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MoveMoveIo\DaData\Facades\DaDataAddress;
use Spatie\ArrayToXml\ArrayToXml;

class SDGSendOutboundJob extends SDGJob
{

    protected string $type = 'Outbound';

    public function generate (bool $frozen = false): string
    {
        $order = $this->extra;
        $order->load(['user' => ['phone'], 'cart' => ['products']]);

        $user_address = isset($order->extra['address']) ? new UserAddress($order->extra['address']) : UserAddress::find($order->user_address_id);
        if (!$user_address->address_full || !$user_address->extra?->city) {
            $user_address = $this->getAddress($user_address);
        }

        $items = DB::table('cart_products')
            ->select([
                'carts.id',
                'carts.order_id',
                'cart_products.product_id',
                'cart_products.product_price_id',
                'cart_products.count',
                'product_prices.expired_at',
                'product_prices.manufactured_at',
                'products.is_frozen',
            ])
            ->leftJoin('carts', 'cart_products.cart_id', 'carts.id')
            ->leftJoin('product_prices', 'product_prices.id', 'cart_products.product_price_id')
            ->leftJoin('products', 'products.id', 'cart_products.product_id')
            ->where('carts.id', $order->cart_id)
            ->where('products.is_frozen', $frozen)
            ->get();

        if ($items->isEmpty()) return '';

        $i = 0;

        $result = [
            '_attributes' => [
                'CCODE'     => config('sdg.ccode'),
                'CITYID'    => '000000854',
                'ORDNR'     => (string)$order->id . ($frozen ? '_FREEZE' : ''),
                'ORDTE'     => Carbon::parse($order->created_at)->format('Ymd'),
                'DLVNR'     => (string)$order->name . ($frozen ? '_FREEZE' : ''),
                'SDATE'     => Carbon::parse($order->delivery_at)->format('Ymd'),
                'SNAME'     => $order->user->name ?? "Без имени",
                'SADDR'     => $user_address->address_full,
                'CRDNT'     => $user_address->address_location ? ($user_address->address_location->latitude . ',' . $user_address->address_location->longitude) : "",
                'CITYNAME'  => $user_address->extra?->city,
                'SINNN'     => '5003094197',
                'SHPID'     => $order->user_id . '-' . $order->name,
                'RMENG'     => (string)$items->count(),
                'MCOST'     => (string)Cart::find($order->cart_id)->cast()->sumFinal,
                'TELEPHONE' => $order->user->phone->value ?? '79067495225',
                'ADINF'     => 'подъезд: ' . ($user_address->entrance ?? "0") . ", этаж: " . ($user_address->floor ?? "0") . ", квартира: " . ($user_address->flat ?? "0") . ". " . $order->comment
            ],
            'ORDRW'       => $items->map(function ($item) use (&$i) {
                return [
                    '_attributes' => [
                        'POSNR'     => $i++,
                        'MATNR'     => $item->product_id,
                        'MMENG'     => (string)(int)$item->count,
                        'MEINH'     => 'PCE',
                        'BBDDT'     => Carbon::parse($item->expired_at)->format('Ymd'),
                        'PRODBBDDT' => Carbon::parse($item->manufactured_at)->format('Ymd'),
                    ]
                ];
            })->toArray()
        ];

        if (in_array($order->time_delivery_slot, [0, 1])) {
            $result['_attributes']['AVSDP'] = CartService::getDeliveryWindowFrom($order->time_delivery_slot);
            $result['_attributes']['AVSDP2'] = CartService::getDeliveryWindowTo($order->time_delivery_slot);
        }

        Integration::create([
            'date' => Carbon::now(),
            'type' => ImapXlsType::OUTBOUND,
            'data' => $result,
        ]);

        $xml = new ArrayToXml(['ORDHD' => $result], [
            'rootElementName' => 'SHPNOTIFICATION'
        ], true, "windows-1251");

        $order->update([
            'system_status' => OrderSystemStatus::SEND_TO_SDG_OUTBOUND
        ]);
        event(new SystemChangeSystemStatusOrderEvent($order, extra: ['system_status' => OrderSystemStatus::SEND_TO_SDG_OUTBOUND]));

        return $xml->prettify()->toXml();
    }

    public function handle()
    {
        try {
            $this->transport = new SDGTransport();
            $this->content = $this->generate();
            if ($this->content) {
                $this->prepareFile();
                $this->saveFile();
                $this->sendFile();
            }

            $this->content = '';

            //2 aфайла - только с заморозкой и без неё
            $this->content = $this->generate(true);
            if ($this->content) {
                $this->prepareFile('FREEZE');
                $this->saveFile();
                $this->sendFile();
            }

        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }

    protected function getAddress (UserAddress $address)
    {
        $dadata = DaDataAddress::geolocate($address->address_location->latitude, $address->address_location->longitude, 1);
        $address->address_full = $dadata['suggestions'][0]['unrestricted_value'] ?? null;
        $address->extra = $dadata['suggestions'][0]['data'] ?? null;
        if ($address->exists) $address->save();
        return $address;
    }
}
