<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserProductActualListRequest extends ApiRequest
{
    public function rules (): array {
        return [
            'order'     => ['nullable', 'in:price,popular,newly,name,discount,expired_at,-price,-popular,-newly,-name,-discount,-expired_at'],
            'limit'     => ['nullable', 'int', 'min:0'],
            'offset'    => ['nullable', 'int', 'min:0'],
            'expired_at_from' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
