<?php

return [
    'TerminalKey'     => env('PAYMENT_TINKOFF_TERMINAL_KEY'),
    'Password'     => env('PAYMENT_TINKOFF_TERMINAL_PASSWORD'),
    'url' => [
        'success' => env('PAYMENT_TINKOFF_URL_SUCCESS'),
        'fail'   => env('PAYMENT_TINKOFF_URL_FAIL'),
        'webhook' => env('PAYMENT_TINKOFF_URL_WEBHOOK'),
    ]
];
