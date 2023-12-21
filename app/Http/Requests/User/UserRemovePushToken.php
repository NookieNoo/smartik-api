<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserRemovePushToken extends ApiRequest
{
    public function rules (): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }
}