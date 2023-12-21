<?php

namespace App\Http\Requests\User;

use App\Enums\UserPaymentType;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UserUpsertPaymentRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'uuid'    => ['required', 'uuid'],
            'name'    => ['nullable', 'string'],
            'method'  => ['required', Rule::in(UserPaymentType::values())],
            'default' => ['nullable', 'boolean'],
        ];
    }
}