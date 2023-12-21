<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPromo extends Model
{
    use SoftDeletes;

    protected $casts = [
        'used_at' => 'datetime',
        'extra'   => 'object',
    ];

    public function promo (): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }
}
