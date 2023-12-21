<?php

namespace App\Jobs\Imap;

use App\Exceptions\Integration\Imap\RepeatSentOrdersToProviderException;
use App\Models\CartProduct;
use App\Models\IntegrationReport;
use App\Models\ProductActual;
use App\Services\Integration\BaseIntegration;
use App\Services\Integration\SmartikIntegration;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImapProcessSendOrdersToProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 1;

    public function __construct (public IntegrationReport $report, public bool $resend = false) {}

    public function handle ()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            return;
        }
        if (!$this->resend && IntegrationReport::where('provider_id', $this->report->provider_id)->where('date', $this->report->date)->where('mailbox_type', ImapXlsType::TO_PROVIDER)->count()) {
            throw new RepeatSentOrdersToProviderException($this->report);
        }

        $result = [];
        $prices = BaseIntegration::getProductsToProvider($this->report->provider_id);

        $prices->each(function ($item) use (&$result) {
            if (!isset($result[$item->product_price_id])) {
                $result[$item->product_price_id] = [
                    //'order_id'         => $item->order_id,
                    'product_id'       => $item->product_id,
                    'product_price_id' => $item->product_price_id,
                    'ean'              => $item->product_ean,
                    'external_id'      => $item->external_id,
                    'name'             => $item->name,
                    'expired_at'       => Carbon::parse($item->expired_at)->format('Y-m-d'),
                    'count'            => 0
                ];
            }
            $result[$item->product_price_id]['count'] = $result[$item->product_price_id]['count'] + (float)$item->count;
        });

        SmartikIntegration::sendToProvider($this->report, $result);

        $orders = $prices->unique('product_price_id');
        //$carts
        foreach ($orders as $item) {
            //CartProduct::holdByOrder($item->order_id);
            CartProduct::holdByProductPrice($item->product_price_id);
        }

        ProductActual::removeUnusedTodayByProvider($this->report->provider_id);
        /*
        $class = "\\App\Services\\Integration\\" . $this->report->provider->slug . "Integration";
        if (class_exists($class) && count($result) && 1 === 2) {
            $class::sendToProvider($this->report, $result);
        }
        */
    }
}