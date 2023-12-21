<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserPromoRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            //'type' => ['required', 'in:cancel,done,delivery'],
            //'id'   => ['required', 'uuid'],
        ];
    }
}