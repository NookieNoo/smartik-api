<?php

namespace App\Models;

use App\Traits\HasLogs;
use App\Traits\LastActive;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ApiUser extends Authenticatable
{
    use Notifiable, SoftDeletes, LastActive;

    protected $hidden = [
        'token',
        'delete_at'
    ];

    public function provider () {
        return $this->belongsTo(Provider::class);
    }
}