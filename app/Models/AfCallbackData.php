<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfCallbackData extends Model
{
    protected $casts = [
        'data' => 'json'
    ];
}
