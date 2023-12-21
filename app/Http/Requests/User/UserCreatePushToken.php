<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserCreatePushToken extends ApiRequest
{
    public function rules (): array
    {
        return [
            'token'      => ['required', 'string'],
            'token_type' => ['nullable', 'in:fcm'],
        ];
    }
}