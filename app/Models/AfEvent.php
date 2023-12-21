<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfEvent extends Model
{
    protected $casts = [
        'payload' => 'json'
    ];
}
