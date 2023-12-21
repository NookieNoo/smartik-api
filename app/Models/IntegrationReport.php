<?php

namespace App\Models;

use App\Services\Integration\Transport\Enums\ImapXlsType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationReport extends Model
{
    protected $casts = [
        'report'       => 'object',
        'extra'        => 'object',
        'date'         => 'date',
        'mailbox_type' => ImapXlsType::class
    ];

    public function provider (): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}