<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiRequest extends FormRequest
{

	public function messages () {
		return [
			'required'         => 'Необходимый параметр',
			'required_without' => 'Необходимый параметр',
			'string'           => 'Неверный формат',
			'boolean'          => 'Неверный формат',
			'integer'          => 'Неверный формат',
			'date'             => 'Указана неверная дата',
			'date_format'      => 'Указана неверная дата',
			'url'              => 'Неверная ссылка',
			'phone'            => 'Неверный формат телефона',
			'sex.in'           => 'Неверно указан пол',
		];
	}
}
