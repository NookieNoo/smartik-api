<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDelivery extends Model
{
    protected $casts = [
        'extra'      => 'object',
        'started_at' => 'datetime',
        'on_way_at'  => 'datetime',
        'radius_at'  => 'datetime',
        'arrival_at' => 'datetime',
    ];
}