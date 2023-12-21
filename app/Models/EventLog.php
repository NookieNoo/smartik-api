<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $casts = [
        'data'  => 'object',
        'extra' => 'object',
        'trace' => 'array'
    ];
}