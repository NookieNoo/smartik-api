<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDebug extends Model
{
    protected $casts = [
        'request_headers'  => 'array',
        'response_headers' => 'array',
        'extra'            => 'object',
    ];
}