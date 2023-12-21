<?php

namespace App\Jobs\Imap;

use App\Enums\OrderStatus;
use App\Enums\OrderSystemStatus;
use App\Events\System\SystemChangeStatusOrderEvent;
use App\Models\IntegrationReport;
use App\Models\Order;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ImapSendOrdersToProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct (
        public bool    $resend = false,
        public ?Carbon $date = null
    ) {}

    public function handle ()
    {
        $integrations = IntegrationReport::query()
            ->joinSub(function ($query) {
                $query->selectRaw('MAX(id) as id')
                    ->from(app(IntegrationReport::class)->getTable())
                    ->where('mailbox_type', ImapXlsType::PRICES)
                    ->where('date', ($this->date ?? Carbon::now())->format('Y-m-d'))
                    ->groupBy('provider_id');
            }, 'latest_id', function ($join) {
                $join->on(app(IntegrationReport::class)->getTable() . '.id', 'latest_id.id');
            })
            ->get();


        Bus::batch($integrations->map(function (IntegrationReport $item) {
            return new ImapProcessSendOrdersToProviderJob($item, resend: $this->resend);
        }))->finally(function (Batch $batch) {
            // отменяем все заказы, что не в работе на 15-00
            Order::query()
                ->where('status', OrderStatus::PAYMENT_DONE)
                ->where('system_status', OrderSystemStatus::IN_APP)
                ->get()
                ->each(function ($order) {
                    $order->update([
                        'status' => OrderStatus::CANCELED_MANAGER
                    ]);
                    event(new SystemChangeStatusOrderEvent($order, extra: ['status' => OrderStatus::CANCELED_MANAGER]));
                });
        })->dispatch();


    }
}