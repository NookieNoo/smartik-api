<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    protected $casts = [
        'data' => 'object'
    ];

    public function payment (): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}