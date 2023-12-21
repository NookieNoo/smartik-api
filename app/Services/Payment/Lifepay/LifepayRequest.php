<?php

namespace App\Services\Payment\Lifepay;

use App\Models\Payment;
use App\Models\PaymentDebug;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LifepayRequest
{
    protected static string $url_input   = 'https://partner.life-pay.ru/alba/input/';
    protected static string $url_details = 'https://partner.life-pay.ru/alba/details/';
    protected static string $url_hold    = 'https://partner.life-pay.ru/alba/process_funds_blocked/';

    protected string         $secret = '';
    protected PendingRequest $http;

    public function __construct (
        protected string $method = 'POST',
        protected string $url = '',
        protected array  $request = []
    )
    {
        $this->http = $this->http();
        $this->secret = config('payment.lifepay.secret');
    }

    private function http (): PendingRequest
    {
        return Http::asForm()->withoutRedirecting()->after(function (RequestInterface $request, ResponseInterface $response, float $time) {

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
                'system'           => 'lifepay',
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
            'check' => $this->sign()
        ];
    }

    protected function sign (): string
    {
        ksort($this->request, SORT_LOCALE_STRING);

        $urlParsed = parse_url($this->url);
        $path = $urlParsed['path'];
        $host = $urlParsed['host'] ?? "";

        if (isset($urlParsed['port']) && $urlParsed['port'] !== 80) {
            $host .= ":" . $urlParsed['port'];
        }

        $method = strtoupper($this->method) === 'POST' ? 'POST' : 'GET';

        $data = implode("\n", [
                $method,
                $host,
                $path,
                $this->rfc3986()
            ]
        );

        return base64_encode(
            hash_hmac("sha256",
                $data,
                $this->secret,
                true
            )
        );
    }

    protected function rfc3986 (): string
    {
        $result = '';
        $seporator = '&';

        foreach ($this->request as $key => $value) {
            if (is_array($value)) $value = implode('', $value);
            $result .= $seporator . $key . '=' . rawurlencode($value ?? '');
        }

        return trim($result, $seporator);
    }

    public static function input (array $request): Response
    {
        $lifepay = new static(
            method: 'POST',
            url: static::$url_input,
            request: $request
        );

        $lifepay->request['version'] = '2.0';
        $lifepay->request['service_id'] = config('payment.lifepay.id');

        if (config('payment.lifepay.url.success')) $lifepay->request['url_success'] = config('payment.lifepay.url.success');
        if (config('payment.lifepay.url.error')) $lifepay->request['url_error'] = config('payment.lifepay.url.error');

        return $lifepay->http->post($lifepay->url, $lifepay->params());
    }

    public static function check (array $request): string
    {
        $lifepay = new static(
            method: 'POST',
            url: config('payment.lifepay.url.webhook'),
            request: $request
        );
        return $lifepay->sign();
    }

    public static function details (array $request): Response
    {
        $lifepay = new static(
            method: 'POST',
            url: static::$url_details,
            request: $request
        );

        $lifepay->request['version'] = '2.0';
        $lifepay->request['service_id'] = config('payment.lifepay.id');

        return $lifepay->http->post($lifepay->url, $lifepay->params());
    }

    public static function unblock (array $request): Response
    {
        $lifepay = new static(
            method: 'POST',
            url: static::$url_hold,
            request: $request
        );

        $lifepay->request['action'] = 'unblock';
        $lifepay->request['version'] = '2.0';

        return $lifepay->http->post($lifepay->url, $lifepay->params());
    }

    public static function charge (array $request): Response
    {
        $lifepay = new static(
            method: 'POST',
            url: static::$url_hold,
            request: $request
        );

        $lifepay->request['action'] = 'charge';
        $lifepay->request['version'] = '2.0';

        return $lifepay->http->post($lifepay->url, $lifepay->params());
    }
}