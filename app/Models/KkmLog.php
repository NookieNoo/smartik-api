<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KkmLog extends Model
{
    protected $casts = [
        'request'  => 'json',
        'response' => 'json'
    ];
}