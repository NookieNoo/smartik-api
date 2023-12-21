<?php

namespace App\Models;

use App\Services\Integration\Transport\Enums\ImapXlsType;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $casts = [
        'date'  => 'date',
        'type'  => ImapXlsType::class,
        'data'  => 'object',
        'extra' => 'object'
    ];
}