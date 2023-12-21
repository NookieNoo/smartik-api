<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status' => PaymentStatus::class,
        'extra'  => 'object'
    ];

    public function logs (): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function order (): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}