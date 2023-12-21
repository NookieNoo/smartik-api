<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserProductSearchRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'query' => ['required', 'string'],
        ];
    }
}