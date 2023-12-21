<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfCallbackDataTemp extends Model
{
    protected $table = 'af_callback_data_temp';
    protected $casts = [
        'data' => 'json'
    ];
}
