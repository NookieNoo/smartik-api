<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ProviderExtraCast implements CastsAttributes
{
    public function get ($model, $key, $value, $attributes)
    {
        $json = json_decode($value ?? '[]', true);

        return (object)[
            'code'        => $json['code'] ?? null,
            'code_at'     => (isset($json['code_at']) && $json['code_at']) ? Carbon::parse($json['code_at'])->format('Y-m-d H:i:s') : null,
            'verified_at' => (isset($json['verified_at']) && $json['verified_at']) ? Carbon::parse($json['verified_at'])->format('Y-m-d H:i:s') : null,
        ];
    }

    public function set ($model, $key, $value, $attributes)
    {
        return json_encode([
            'code'        => (string)$value?->code ?? null,
            'code_at'     => Carbon::parse($value?->code_at)?->format('Y-m-d H:i:s'),
            'verified_at' => Carbon::parse($value?->verified_at)?->format('Y-m-d H:i:s'),
        ]);
    }
}