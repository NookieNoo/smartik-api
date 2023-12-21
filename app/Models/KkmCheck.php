<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KkmCheck extends Model
{
    use SoftDeletes;

    protected $casts = [
        'check'  => 'array',
        'extra'  => 'json',
        'result' => 'object',
    ];
}