<?php

return [
    'id'     => env('PAYMENT_LIFEPAY_ID'),
    'secret' => env('PAYMENT_LIFEPAY_SECRET'),

    'url' => [
        'success' => env('PAYMENT_LIFEPAY_URL_SUCCESS'),
        'error'   => env('PAYMENT_LIFEPAY_URL_ERROR'),
        'webhook' => env('PAYMENT_LIFEPAY_URL_WEBHOOK'),
    ]
];