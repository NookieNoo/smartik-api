<?php

namespace App\Http\Requests\External;

use App\Http\Requests\ApiRequest;

class AtsCancelRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'at' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}