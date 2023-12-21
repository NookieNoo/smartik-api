<?php

namespace App\Jobs\SDG;

use App\Enums\CartProductStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderSystemStatus;
use App\Enums\PaymentStatus;
use App\Events\System\SystemChangeStatusCartProductEvent;
use App\Events\System\SystemChangeStatusOrderEvent;
use App\Events\System\SystemChangeSystemStatusOrderEvent;
use App\Exceptions\Integration\SDG\ParseSHPPaymentNotInHoldException;
use App\Facades\Gosnumber;
use App\Models\CartProduct;
use App\Models\Integration;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\ProductPrice;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use App\Services\Payment\PaymentCancelCause;
use App\Services\Payment\PaymentInterface;
use App\Services\SDGTransport;
use App\Services\Showcase\CartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SDGProcessWBLJob
{
    public function __construct (
        public string $file,
        public string $remote
    ) {}

    public function handle (SDGTransport $transport)
    {
        try {
            $xml = simplexml_load_string(file_get_contents($this->file), "SimpleXMLElement", LIBXML_NOCDATA);

            foreach ($xml->waybill as $way) {
                $issueNumber = str_replace('_FREEZE', '', $way->issueNumber);
                $order = Order::query()
                    ->where('id', $issueNumber)
                    ->orWhere('name', $issueNumber)
                    ->firstOrFail();

                if (!OrderDelivery::where('order_id', $order->id)->count()) {
                    OrderDelivery::create([
                        'order_id'          => $order->id,
                        'vehicle'           => $xml->waybill->transportInfo->vehicleMark ?? null,
                        'gosnumber'         => Gosnumber::parse($xml->waybill->transportInfo->transportNumber->vehicleNumber ?? "")->get(),
                        'forwarder_phone'   => $way->forwarder_telephone ?? null,
                    ]);
                }

                $order->update([
                    'system_status' => OrderSystemStatus::GET_FROM_SDG_WBL,
                    'status'        => OrderStatus::DELIVERY_CREATED
                ]);
                event(new SystemChangeSystemStatusOrderEvent($order, extra: ['system_status' => OrderSystemStatus::GET_FROM_SDG_WBL]));
                event(new SystemChangeStatusOrderEvent($order, extra: ['status' => OrderStatus::DELIVERY_CREATED]));
            }

            Integration::create([
                'date' => Carbon::now(),
                'type' => ImapXlsType::WBL,
                'data' => (array)$xml,
            ]);

            $transport->move($this->remote, 'Out/Ok');
        } catch (\Exception $e) {
            $transport->move($this->remote, 'Out/Bad', 'payment_not_in_hold');
            throw $e;
        }
    }
}
