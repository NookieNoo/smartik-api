<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProduct extends Model
{
    protected $casts = [
        'extra' => 'json'
    ];
}