<?php

// https://docs.devinotele.com/httpapi.html

return [
    'auth_plain' => [
        'login'    => env('DEVINO_LOGIN', ''),
        'password' => env('DEVINO_PASSWORD', ''),
    ],
    'sender'     => env('DEVINO_SENDER', 'Pokupkin'),
    'url'        => env('DEVINO_URL', 'https://integrationapi.net/rest/v2/Sms'),
    'validity'   => env('DEVINO_VALIDITY', 172800),
];
