<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserUpdateProfileRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'name'        => ['nullable', 'string'],
            'email'       => ['nullable', 'email'],
            'sex'         => ['nullable', 'in:man,woman'],
            'birthday_at' => ['nullable', 'date_format:Y-m-d']
        ];
    }
}