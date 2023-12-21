<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserActivateSmsRequest extends ApiRequest
{
    public function rules (): array
    {
        return [
            'phone' => ['required', 'phone:AUTO,mobile'],
            'code'  => ['required', 'size:4'],
        ];
    }

    protected function prepareForValidation (): void
    {
        if ($this->input('phone') && substr($this->input('phone'), 0, 1) !== '+') {
            $this->merge([
                'phone' => '+' . $this->input('phone'),
            ]);
        }
    }
}