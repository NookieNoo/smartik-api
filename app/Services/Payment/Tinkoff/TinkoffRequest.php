<?php

namespace App\Services\Payment\Tinkoff;

use App\Models\Payment;
use App\Models\PaymentDebug;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TinkoffRequest
{
    protected static string $url_init   = 'https://securepay.tinkoff.ru/v2/Init/';
    protected static string $url_details = 'https://securepay.tinkoff.ru/v2/GetState/';
    protected static string $url_confirm    = 'https://securepay.tinkoff.ru/v2/Confirm/';
    protected static string $url_get_card_list    = 'https://securepay.tinkoff.ru/v2/GetCardList/';
    protected static string $url_remove_card    = 'https://securepay.tinkoff.ru/v2/RemoveCard/';
    protected static string $url_cancel    = 'https://securepay.tinkoff.ru/v2/Cancel/';

    protected PendingRequest $http;
    protected string         $password = '';

    public function __construct (
        protected string $method = 'POST',
        protected string $url = '',
        protected array  $request = []
    )
    {
        $this->http = $this->http();
        $this->password = config('payment.tinkoff.Password');
    }

    private function http (): PendingRequest
    {
        return Http::asJson()->withoutRedirecting()->after(function (RequestInterface $request, ResponseInterface $response, float $time) {

            $order = 0;
            $payment = 0;

            if ($string = $request->getBody()) {
                parse_str($string, $out);
                if ($out['order_id'] ?? false) {
                    $tmp = Payment::where('uuid', $out['order_id'])->first();
                    if ($tmp) {
                        $order = $tmp->order_id;
                        $payment = $tmp->id;
                    }
                }
            }
            if (!$order && $json = json_decode($response->getBody(), true)) {
                if ($json['order_id'] ?? false) {
                    $tmp = Payment::where('uuid', $json['order_id'])->first();
                    if ($tmp) {
                        $order = $tmp->order_id;
                        $payment = $tmp->id;
                    }
                }
            }

            PaymentDebug::create([
                'system'           => 'tinkoff',
                'order_id'         => $order,
                'payment_id'       => $payment,
                'side'             => 'out',
                'request_url'      => $request->getUri(),
                'request_method'   => $request->getMethod(),
                'request_headers'  => $request->getHeaders(),
                'request_body'     => $request->getBody(),
                'response_code'    => $response->getStatusCode(),
                'response_headers' => $response->getHeaders(),
                'response_body'    => $response->getBody(),
                'time'             => $time
            ]);
        });
    }

    public function params (): array
    {
        return [
            ...$this->request,
            'Token' => $this->sign()
        ];
    }

    protected function sign (): string
    {
        $this->request['Password'] = $this->password;
        ksort($this->request, SORT_LOCALE_STRING);
        $values = array_values($this->request);
        $str = implode('', $values);
        return hash('sha256', $str);
    }

    public static function init(array $request, bool $recurrent, ?string $userId = null): Response
    {
        $tinkoff = new static(
            method: 'POST',
            url: static::$url_init,
            request: $request
        );

        $tinkoff->request['TerminalKey'] = config('payment.tinkoff.TerminalKey');

        if ($recurrent) {
            $tinkoff->request['Recurrent'] = 'Y';
            $tinkoff->request['CustomerKey'] = $userId;
        }

        if (config('payment.tinkoff.url.success')) $tinkoff->request['SuccessURL'] = config('payment.tinkoff.url.success');
        if (config('payment.tinkoff.url.fail')) $tinkoff->request['FailURL'] = config('payment.tinkoff.url.fail');
        if (config('payment.tinkoff.url.webhook')) $tinkoff->request['NotificationURL'] = config('payment.tinkoff.url.webhook');

        return $tinkoff->http->post($tinkoff->url, $tinkoff->params());
    }

    public static function charge (array $request): Response
    {
        $tinkoff = new static(
            method: 'POST',
            url: static::$url_confirm,
            request: $request
        );

        $tinkoff->request['TerminalKey'] = config('payment.tinkoff.TerminalKey');

        return $tinkoff->http->post($tinkoff->url, $tinkoff->params());
    }

    public static function unblock (array $request): Response
    {
        $tinkoff = new static(
            method: 'POST',
            url: static::$url_cancel,
            request: $request
        );

        $tinkoff->request['TerminalKey'] = config('payment.tinkoff.TerminalKey');

        return $tinkoff->http->post($tinkoff->url, $tinkoff->params());
    }

    public static function getCardList(array $request): Response
    {
        $tinkoff = new static(
            method: 'POST',
            url: static::$url_get_card_list,
            request: $request
        );

        $tinkoff->request['TerminalKey'] = config('payment.tinkoff.TerminalKey');

        return $tinkoff->http->post($tinkoff->url, $tinkoff->params());
    }

    public static function removeCard(array $request): Response
    {
        $tinkoff = new static(
            method: 'POST',
            url: static::$url_remove_card,
            request: $request
        );

        $tinkoff->request['TerminalKey'] = config('payment.tinkoff.TerminalKey');

        return $tinkoff->http->post($tinkoff->url, $tinkoff->params());
    }

    public static function check (array $request): string
    {
        $tinkoff = new static(
            method: 'POST',
            url: config('payment.tinkoff.url.webhook'),
            request: $request
        );
        return $tinkoff->sign();
    }
}
