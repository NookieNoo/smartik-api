<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserCreateOrderRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'comment' => ['nullable', 'string'],
            'time_delivery_at' => ['nullable', 'integer'],
        ];
    }
}
