<?php

return [
    'ftp' => [
        'login' => env('SDG_FTP_LOGIN'),
        'pass'  => env('SDG_FTP_PASS'),
        'host'  => env('SDG_FTP_HOST'),
        'dir'   => env('SDG_FTP_DIR'),
    ],

    'ccode' => env('SDG_CCODE', '000011683'),
    'marks_delimiter' => ' ; '
];
